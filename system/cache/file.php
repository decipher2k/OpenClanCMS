<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

function cs_cache_token($name, $ttl = 0) {

  $token = empty($ttl) ? $name : 'ttl_' . $name;
  $token = preg_replace("=[^a-z0-9._-]=i", '_', (string) $token);
  $token = trim($token, '._');

  return empty($token) ? 'cache' : $token;
}

function cs_cache_has_object($content) {

  $length = strlen($content);
  for($pos = 0; $pos < $length; $pos++) {
    $char = $content[$pos];

    if(($char == 'O' OR $char == 'C') AND isset($content[$pos + 1]) AND $content[$pos + 1] == ':')
      return true;

    if($char == 's' AND isset($content[$pos + 1]) AND $content[$pos + 1] == ':') {
      $len_start = $pos + 2;
      $len_end = strpos($content, ':', $len_start);
      if($len_end === false)
        return false;

      $string_length = (int) substr($content, $len_start, $len_end - $len_start);
      $quote_pos = strpos($content, '"', $len_end);
      if($quote_pos === false)
        return false;

      $pos = $quote_pos + $string_length + 1;
    }
  }

  return false;
}

function cs_cache_clear() {

  $content = cs_paths('uploads/cache');
  unset($content['index.html'], $content['.htaccess'], $content['web.config']);

  foreach($content AS $file => $name)
    unlink('uploads/cache/' . $file);

  $unicode = extension_loaded('unicode') ? 1 : 0;
  $where = "options_mod = 'clansphere' AND options_name = 'cache_unicode'";
  cs_sql_update(__FILE__, 'options', array('options_value'), array($unicode), 0, $where); 
}

function cs_cache_delete($name, $ttl = 0) {

  $token = cs_cache_token($name, $ttl);
  if(file_exists('uploads/cache/' . $token . '.tmp'))
    unlink('uploads/cache/' . $token . '.tmp');
}

function cs_cache_info() {

  $info = cs_paths('uploads/cache');
  unset($info['index.html'], $info['.htaccess'], $info['web.config']);

  $form = array();
  foreach($info AS $filename => $num)
    $form[$filename] = array('name' => $filename, 'time' => filemtime('uploads/cache/' . $filename), 'size' => filesize('uploads/cache/' . $filename));

  $form = array_values($form);
  return $form;
}

function cs_cache_load($name, $ttl = 0) {

  $token = cs_cache_token($name, $ttl);
  if(file_exists('uploads/cache/' . $token . '.tmp')) {

    if(empty($ttl) OR filemtime('uploads/cache/' . $token . '.tmp') >= (time() - $ttl)) {
      $content = file_get_contents('uploads/cache/' . $token . '.tmp');
      if($content === false)
        return false;

      if(cs_cache_has_object($content)) {
        cs_error('uploads/cache/' . $token . '.tmp', 'cs_cache_load - Refused serialized object in cache file');
        return false;
      }

      if(version_compare(phpversion(), '7.0.0', '>='))
        return unserialize($content, array('allowed_classes' => false));
      else
        return unserialize($content);
    }
  }

  return false;
}

function cs_cache_save($name, $content, $ttl = 0) {

  $token = cs_cache_token($name, $ttl);
  cs_cache_delete($token);

  global $cs_main;
  if(is_bool($content))
    cs_error($name, 'cs_cache_save - It is not allowed to just store a boolean');
  elseif(is_writeable('uploads/cache/')) {
    $store = serialize($content);
    $cache_file = 'uploads/cache/' . $token . '.tmp';
    $save_cache = fopen($cache_file, 'w');
    if($save_cache !== false) {
      # set stream encoding if possible to avoid converting issues
      if(function_exists('stream_encoding'))
        stream_encoding($save_cache, $cs_main['charset']);
      if(function_exists('flock'))
        flock($save_cache, LOCK_EX);
      fwrite($save_cache, $store);
      if(function_exists('flock'))
        flock($save_cache, LOCK_UN);
      fclose($save_cache);
      chmod($cache_file, 0644);
    }
    elseif($cs_main['mod'] != 'install')
      cs_error($cache_file, 'cs_cache_save - Unable to write cache file');
  }
  elseif($cs_main['mod'] != 'install')
    cs_error('uploads/cache/' . $token . '.tmp', 'cs_cache_save - Unable to write cache file');

  return $content;
}
