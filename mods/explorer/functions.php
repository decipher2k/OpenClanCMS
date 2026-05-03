<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

function cs_remove_dir($path) {

  global $cs_main;
  $safe_path = cs_safe_path($cs_main['def_path'], $path, 1);
  if($safe_path === false OR $safe_path == realpath($cs_main['def_path']))
    return false;
  $path = $safe_path;
  
  if (substr($path, -1, 1) != '/')
    $path .= '/';

  $normal_files = glob($path . '*');
  $hidden_files = glob($path . '\.?*');
  if(!is_array($normal_files)) $normal_files = array();
  if(!is_array($hidden_files)) $hidden_files = array();
  $all_files = array_merge($normal_files, $hidden_files);

  foreach ($all_files as $file) {

    if (preg_match("/(\.|\.\.)$/", $file))
      continue;

    if (is_file($file) === TRUE)
      unlink($file);
    elseif (is_dir($file) === TRUE)
      cs_remove_dir($file);
  }

  if (is_dir($path) === TRUE)
    rmdir($path);     
}

function cs_explorer_path($path, $method, $sub = 0) {

  global $cs_main;
  # this function should be used to handle path data in and from urls
  # the following escaped slash sign should only reside here
  $slash_esc = '@_@';

  if($method == 'raw')
    $path = str_replace($slash_esc,'/',$path);

  $path = str_replace('\\', '/', (string) $path);
  $path = preg_replace("=/+=", '/', $path);
  $path = str_replace('..', '', $path);
  $path = ($path == '/') ? $path : rtrim($path, '/');

  if(!empty($sub))
    $path = substr($path, 0, strrpos($path, '/'));

  if($method == 'raw') {
    $safe_path = cs_safe_path($cs_main['def_path'], $path, 0);
    if($safe_path === false)
      return '';
    $base = str_replace('\\', '/', realpath($cs_main['def_path']));
    $safe_path = str_replace('\\', '/', $safe_path);
    $path = ltrim(substr($safe_path, strlen($base)), '/');
    $path = empty($path) ? '.' : $path;
  }

  if($method == 'escape')
    $path = str_replace('/',$slash_esc,$path);

  return $path;
}

function cs_explorer_denied($path) {

  $ending = strtolower(substr(strrchr(str_replace('\\', '/', $path), '.'), 1));
  $denied = array('php' => 1, 'php3' => 1, 'php4' => 1, 'php5' => 1, 'php7' => 1,
                  'phtml' => 1, 'phar' => 1, 'inc' => 1, 'htaccess' => 1, 'config' => 1);
  return isset($denied[$ending]);
}
