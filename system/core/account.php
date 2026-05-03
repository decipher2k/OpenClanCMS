<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

function cs_login_cookies($userid = 0, $use_old_hash = 0) {

  global $account, $cs_main;
  $lifetime = empty($userid) ? 1 : $cs_main['cookie']['lifetime'];
  $thistime = empty($userid) ? '' : cs_time();
  $thishash = empty($use_old_hash) ? '' : $use_old_hash;

  if(!empty($userid) AND empty($use_old_hash)) {
    $thishash = cs_random_string(64);

    $cells = array('users_cookietime', 'users_cookiehash');
    $content = array($thistime, $thishash);
    cs_sql_update(__FILE__,'users',$cells,$content,$userid, 0, 0);
  }
  elseif(!empty($userid) AND $use_old_hash == true) {
    $thistime = $account['users_cookietime'];
    $thishash = $account['users_cookiehash'];

    if(empty($thistime) OR empty($thishash)) {
      cs_login_cookies($userid);
      return true;
    }
  }

  $secure = !empty($_SERVER['HTTPS']) AND strtolower($_SERVER['HTTPS']) != 'off';
  $cookie_opts = array('expires' => $lifetime, 'path' => $cs_main['cookie']['path'],
    'domain' => $cs_main['cookie']['domain'], 'secure' => $secure, 'httponly' => true,
    'samesite' => 'Lax');
  setcookie('cs_userid', $userid, $cookie_opts);
  setcookie('cs_cookietime', $thistime, $cookie_opts);
  setcookie('cs_cookiehash', $thishash, $cookie_opts);
}

global $cs_lang, $cs_main, $login;

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

$login   = array('mode' => FALSE, 'error' => '', 'cookie' => 0);
$noacc   = array('users_id' => 0, 'users_pwd' => '', 'users_cookiehash' => '', 'users_cookietime' => 0);
$account = $noacc;

# Send cookie only by http protocol
$secure = !empty($_SERVER['HTTPS']) AND strtolower($_SERVER['HTTPS']) != 'off';
session_set_cookie_params(array('lifetime' => 0, 'path' => $cs_main['cookie']['path'],
  'domain' => $cs_main['cookie']['domain'], 'secure' => $secure, 'httponly' => TRUE,
  'samesite' => 'Lax'));

session_name('cs' . md5($cs_main['cookie']['domain'])); 
session_start();

# xsrf protection
if($cs_main['xsrf_protection'] === TRUE AND empty($_POST) AND !empty($_GET) AND cs_xsrf_needs_get_key($_GET)) {
  $needed_keys = isset($_SESSION['cs_xsrf_keys']) ? $_SESSION['cs_xsrf_keys'] : array();
  $given_key = isset($_GET['cs_xsrf_key']) ? $_GET['cs_xsrf_key'] : '';
  if(empty($given_key) OR !in_array($given_key, $needed_keys, true)) {
    $_SESSION['cs_xsrf_keys'] = array();
    $referer = empty($_SERVER['HTTP_REFERER']) ? 'empty' : $_SERVER['HTTP_REFERER'];

    if(!empty($cs_main['developer']))
      cs_error(__FILE__, 'XSRF Protection triggered for GET, Referer: ' . $referer);

    cs_redirect(false, $cs_main['def_mod'], $cs_main['def_action']);
  }
}

if($cs_main['xsrf_protection']===TRUE && !empty($_POST)) {
  $needed_keys = isset($_SESSION['cs_xsrf_keys']) ? $_SESSION['cs_xsrf_keys'] : array();
  $given_key = isset($_POST['cs_xsrf_key']) ? $_POST['cs_xsrf_key'] : '';
  if(empty($given_key) || !in_array($given_key, $needed_keys, true)) {
    $_SESSION['cs_xsrf_keys'] = array();
    $referer = empty($_SERVER['HTTP_REFERER']) ? 'empty' : $_SERVER['HTTP_REFERER'];

    if(!empty($cs_main['developer']))
      cs_error(__FILE__, 'XSRF Protection triggered: Array(' . implode(', ', $needed_keys) . ') does not include "' . $given_key . '", Referer: ' . $referer);

    cs_redirect(false, $cs_main['def_mod'], $cs_main['def_action']);
  }
}

if(empty($_SESSION['users_id'])) {

  if(isset($_POST['login'])) {
    $login['method'] = 'form';
    $login['nick'] = $_POST['nick'];
    $login['password'] = $_POST['password'];

    $now = cs_time();
    if(!isset($_SESSION['login_attempts']))
      $_SESSION['login_attempts'] = array('count' => 0, 'last' => 0, 'locked' => 0);

    if($_SESSION['login_attempts']['locked'] > $now) {
      $login['error'] = 'user_login_notfound';
      unset($_POST['login']);
    }

    if(isset($_POST['cookie'])) {
      $login['cookie'] = $_POST['cookie'];
    }
    $data['options'] = cs_sql_option(__FILE__,'users');
    $login_where = 'users_nick = ?';
    $login_params = array($login['nick']);
    if($data['options']['login'] == 'email') {
      $login_where = 'users_email = ?';
      $login_params = array($login['nick']);
    }
  }
  elseif(isset($_COOKIE['cs_userid']) AND isset($_COOKIE['cs_cookietime']) AND isset($_COOKIE['cs_cookiehash'])) {
    $login['method'] = 'cookie';
    $login['userid'] = (int) $_COOKIE['cs_userid'];
    $login['cookietime'] = (int) $_COOKIE['cs_cookietime'];
    $login['cookiehash'] = $_COOKIE['cs_cookiehash'];
    $login_where = 'users_id = ?';
    $login_params = array((int) $login['userid']);
  }

  if(isset($login['method'])) {
    $login_db = cs_sql_select(__FILE__,'users','*',$login_where,0,0,1,0,$login_params);

    if(!empty($login_db['users_pwd']) AND ($login['method'] == 'cookie' OR cs_password_verify($login['password'], $login_db['users_pwd'], $cs_db['hash']))) {
      if(empty($login_db['users_active']) || !empty($login_db['users_delete']))
        $login['error'] = 'closed'; 
      elseif($login['method'] == 'cookie' AND ($login['cookietime'] < $login_db['users_cookietime'] OR $login['cookietime'] > cs_time()))
        $login['error'] = 'user_login_notfound';
      elseif($login['method'] == 'cookie' AND !cs_hash_equals($login_db['users_cookiehash'], $login['cookiehash']))
        $login['error'] = 'user_login_notfound';
      else {
        $login['mode'] = TRUE;

        $_SESSION['users_id'] = $login_db['users_id'];
        $_SESSION['users_ip'] = cs_getip();
        $_SESSION['users_agent'] = $user_agent;
        $_SESSION['users_pwd'] = $login_db['users_pwd'];

        if(!empty($login['password']) AND $login_db['users_pwd'][0] !== '$') {
          $new_hash = cs_password_hash($login['password']);
          cs_sql_update(__FILE__,'users',array('users_pwd'),array($new_hash),$login_db['users_id']);
          $_SESSION['users_pwd'] = $new_hash;
        }

        if($login['method'] == 'form')
          $_SESSION['login_attempts'] = array('count' => 0, 'last' => 0, 'locked' => 0);
      }
    }
    elseif(!empty($login_db['users_id']) OR $login['method'] == 'form') {
      $login['error'] = 'user_login_notfound';

      if($login['method'] == 'form' AND !isset($_SESSION['login_attempts']['locked'])) {
        $attempt = &$_SESSION['login_attempts'];
        $attempt['count']++;
        $attempt['last'] = $now;
        if($attempt['count'] >= 10)
          $attempt['locked'] = $now + 900;
        elseif($attempt['count'] >= 5)
          $attempt['locked'] = $now + 30;
      }
    }
    else {
      $login['error'] = 'user_login_notfound';
    }

    if(!empty($login['cookie']) AND !empty($login['mode'])) {
      $login['method'] = 'form_cookie';
      cs_login_cookies($login_db['users_id'], true);
    }
    session_regenerate_id(true);
    unset($login_db);
  }
}

if(!empty($_SESSION['users_id'])) {

  if (empty($login['method'])) $login['method'] = 'session';
  $login['mode'] = TRUE;

  $account = cs_sql_select(__FILE__, 'users', '*', 'users_id = ? AND users_active = 1 AND users_delete = 0',0,0,1,0,array((int) $_SESSION['users_id']));
  if (empty($account) OR ($account['users_pwd'] != $_SESSION['users_pwd'])) {
    session_destroy();
    $login['mode'] = FALSE;
    $account = $noacc;
  }
  if (empty($cs_main['ajax'])) $account['users_ajax'] = 0;
}

if(isset($_COOKIE['cs_userid'])) {
  # refresh cookie lifetime after a while
  if(isset($_COOKIE['cs_cookiehash']) AND isset($_COOKIE['cs_cookietime']) AND $_COOKIE['cs_cookietime'] < (cs_time() - 43200))
    cs_login_cookies($_COOKIE['cs_userid'], $_COOKIE['cs_cookiehash']);

  # empty old and bad cookie data
  if(empty($_COOKIE['cs_cookiehash']) OR !cs_hash_equals($account['users_cookiehash'], $_COOKIE['cs_cookiehash']))
    cs_login_cookies();
}

$time = cs_time();
if(!empty($account['users_id'])) {
  if($_SESSION['users_ip'] != cs_getip() OR $_SESSION['users_agent'] != $user_agent) {
    session_destroy();
    $login['mode'] = FALSE;
  }
  elseif($cs_main['mod'] == 'users' AND $cs_main['action'] == 'logout') {
    cs_login_cookies();
    session_destroy();
    $login['mode'] = FALSE;
  }
  elseif($time > ($account['users_laston'] + 30)) {
    $cells = array('users_laston');
    $content = array($time);
    cs_sql_update(__FILE__,'users',$cells,$content,$account['users_id'], 0, 0);
  }
}
else
  $account = array('access_id' => 1, 'users_id' => 0, 'users_lang' => $cs_main['def_lang'], 'users_limit' => $cs_main['data_limit'],
                   'users_timezone' => $cs_main['def_timezone'], 'users_dstime' => $cs_main['def_dstime'], 'access_clansphere' => 0);

$gma = cs_sql_select(__FILE__,'access','*','access_id = ?', 0,0,1, 'access_' . $account['access_id'], array((int) $account['access_id']));
if(is_array($gma))
  $account = array_merge($account,$gma);

if(empty($cs_main['maintenance_access']))
  $cs_main['maintenance_access'] = 3;

if(empty($cs_main['public']) AND !empty($account['users_id']) AND $account['access_clansphere'] < $cs_main['maintenance_access']) {
  cs_login_cookies();
  session_destroy();
  $login['mode'] = FALSE;
  $login['error'] = 'not_public'; 
}

if($account['users_limit'] < 0) $account['users_limit'] = $cs_main['data_limit'];
unset($account['users_pwd'], $account['users_cookiehash'], $account['users_cookietime']);
