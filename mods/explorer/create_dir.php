<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('explorer');

include_once 'mods/explorer/functions.php';

$target = empty($_REQUEST['dir']) ? '' : $_REQUEST['dir'];
$target = empty($_POST['data_folder']) ? $target : $_POST['data_folder'];
$dir = cs_explorer_path($target, 'raw');
$lsd = cs_explorer_path($dir, 'escape');

if(empty($_POST['submit'])) {

  $data = array();
  $data['var']['dir'] = $dir;

  echo cs_subtemplate(__FILE__, $data, 'explorer', 'create_dir');
}
else {

  if (substr($dir,-1,1) != '/' && !empty($dir)) $dir .= '/';

  $folder_name = cs_safe_filename($_POST['folder_name']);
  if($folder_name === false)
    die(cs_error_internal(0, 'Invalid directory name'));
  $dir_created = cs_safe_path($cs_main['def_path'], $dir . $folder_name, 0);
  if($dir_created === false)
    die(cs_error_internal(0, 'Invalid target path'));

  $message = mkdir($dir_created, 0755) ? $cs_lang['success'] : $cs_lang['error'];

  cs_redirect($message,'explorer','roots','dir=' . $lsd);
}
