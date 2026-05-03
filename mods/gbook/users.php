<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('gbook');
$cs_post = cs_post('id,where,start');
$cs_get = cs_get('id,where,sort');

$where = empty($cs_get['id']) ? 0 : $cs_get['id'];
if (!empty($cs_post['id']))  $where = $cs_post['id'];
$start = empty($cs_get['start']) ? 0 : $cs_get['start'];
if (!empty($cs_post['start']))  $start = $cs_post['start'];

if(empty($where)) {
 $where = empty($cs_get['where']) ? 0 : $cs_get['where'];
 if (!empty($cs_post['where']))  $where = $cs_post['where'];
}

$gbook_count = cs_sql_count(__FILE__,'gbook','gbook_users_id = ? AND gbook_lock = 1',0,array($where));
...
$cs_gbook = cs_sql_select(__FILE__,$from,$select,$where,$order,$start,$account['users_limit'],0,$where_params);
$gbook_loop = count($cs_gbook);

$c = 0;

for($run=0; $run<$gbook_loop; $run++)
{
  $entry_count = $gbook_count - $start - $c;
  $c++;
  $gbook[$run]['entry_count'] = $entry_count;
  if($cs_gbook[$run]['users_id'] == 0) {
    $gbook[$run]['users_nick'] = cs_secure($cs_gbook[$run]['gbook_nick']);
    $gbook[$run]['town'] = '';
    $gbook[$run]['icon_town'] = '';
    if (!empty($cs_gbook[$run]['gbook_town'])) {
      $gbook[$run]['icon_town'] = cs_icon('gohome');
      $gbook[$run]['town'] = cs_secure($cs_gbook[$run]['gbook_town']);
    }
    $mail = cs_secure($cs_gbook[$run]['gbook_email']);
    $gbook[$run]['icon_mail'] = empty($mail) ? '' : cs_html_link("mailto:$mail",cs_icon('mail_generic'));
    $icq = cs_secure($cs_gbook[$run]['gbook_icq']);
    $gbook[$run]['icon_icq'] = empty($icq) ? '' : cs_html_link("http://www.icq.com/$icq",cs_icon('licq'));
    $jabber = cs_secure($cs_gbook[$run]['gbook_jabber']);
    $gbook[$run]['icon_jabber'] = empty($jabber) ? '' : cs_html_jabbermail($jabber,cs_icon('jabber_protocol'));
    $skype = cs_secure($cs_gbook[$run]['gbook_skype']);
    $url = 'http://mystatus.skype.com/smallicon/' . $skype;
    $gbook[$run]['icon_skype'] = empty($skype) ? '' : cs_html_link("skype:$skype?userinfo",cs_html_img($url,'16','16','0','Skype'),'0');
    $url = cs_secure($cs_gbook[$run]['gbook_url']);
    $gbook[$run]['icon_url'] = empty($url) ? '' : cs_html_link("http://$url",cs_icon('gohome'));
  }
  else
  {
    $hidden = explode(',',$cs_gbook[$run]['users_hidden']);
    $allow = $cs_gbook[$run]['users_id'] == $account['users_id'] OR $account['access_users'] > 4 ? 1 : 0;
    $gbook[$run]['users_nick'] = cs_user($cs_gbook[$run]['users_id'],$cs_gbook[$run]['users_nick'], $cs_gbook[$run]['users_active'], $cs_gbook[$run]['users_delete']);
        $gbook[$run]['town'] = '';
    $gbook[$run]['icon_town'] = '';
    if (!empty($cs_gbook[$run]['users_place'])) {
      $gbook[$run]['icon_town'] = cs_icon('gohome');
      $gbook[$run]['town'] = cs_secure($cs_gbook[$run]['users_place']);
    }
    $mail = cs_secure($cs_gbook[$run]['users_email']);
    if(in_array('users_email',$hidden)) {
      $mail = empty($allow) ? '' : $mail;
    }
    $gbook[$run]['icon_mail'] = empty($mail) ? '' : cs_html_link("mailto:$mail",cs_icon('mail_generic'));
    $icq = cs_secure($cs_gbook[$run]['users_icq']);
    if(in_array('users_icq',$hidden)) {
      $icq = empty($allow) ? '' : $icq;
    }
    $gbook[$run]['icon_icq'] = empty($icq) ? '' : cs_html_link("http://www.icq.com/$icq",cs_icon('licq'));
    $jabber = cs_secure($cs_gbook[$run]['users_jabber']);
    if(in_array('users_jabber',$hidden)) {
      $jabber = empty($allow) ? '' : $jabber;
    }
    $gbook[$run]['icon_jabber'] = empty($jabber) ? '' : cs_html_jabbermail($jabber,cs_icon('jabber_protocol'));
    $skype = cs_secure($cs_gbook[$run]['users_skype']);
    $url = 'http://mystatus.skype.com/smallicon/' . $skype;
    $skype = cs_html_link('skype:' . $cs_gbook[$run]['users_skype'] . '?userinfo',cs_html_img($url,'16','16','0','Skype'),'0');
    if(in_array('users_skype',$hidden)) {
      $skype = empty($allow) ? '' : $skype;
    }
    $gbook[$run]['icon_skype'] = empty($cs_gbook[$run]['users_skype']) ? '' : $skype;
    $url = cs_secure($cs_gbook[$run]['users_url']);
    if(in_array('users_url',$hidden)) {
      $url = empty($allow) ? '' : $url;
    }
    $gbook[$run]['icon_url'] = empty($url) ? '' : cs_html_link("http://$url",cs_icon('gohome'));
  }
  $gbook[$run]['text'] = cs_secure($cs_gbook[$run]['gbook_text'],1,1);
  $gbook[$run]['time'] = cs_date('unix',$cs_gbook[$run]['gbook_time'],1);
  if($account['access_gbook'] >= 4)
  {
    $img_edit = cs_icon('edit',16,$cs_lang['edit']);
    $gbook[$run]['icon_edit'] = cs_link($img_edit,'gbook','edit','id=' . $cs_gbook[$run]['gbook_id'] . '&amp;from=users',0,$cs_lang['edit']);
    $img_del = cs_icon('editdelete',16,$cs_lang['remove']);
    $gbook[$run]['icon_remove'] = cs_link($img_del,'gbook','remove','id=' . $cs_gbook[$run]['gbook_id'] . '&amp;from=users',0,$cs_lang['remove']);
    $ip = $cs_gbook[$run]['gbook_ip'];
    if($account['access_gbook'] == 4) {
      $last = strlen(substr(strrchr ($cs_gbook[$run]['gbook_ip'], '.'), 1 ));
      $ip = strlen($gbook_ip);
      $ip = substr($gbook_ip,0,$ip-$last);
      $ip = $ip . '*';
    }
    $ip_show = empty($ip) ? '-' : $ip;
    $gbook[$run]['icon_ip'] = cs_html_img('symbols/' . $cs_main['img_path'] . '/16/important.' . $cs_main['img_ext'],16,16,'title="'. $ip_show .'"');
  }
  else{
    $gbook[$run]['icon_edit'] = '';
    $gbook[$run]['icon_remove'] = '';
    $gbook[$run]['icon_ip'] = '';
  }
}

$data['gbook'] = !empty($gbook) ? $gbook : '';
echo cs_subtemplate(__FILE__,$data,'gbook','users');
