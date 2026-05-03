<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

include_once 'mods/explorer/functions.php';

$cs_lang = cs_translate('explorer');

$dir = cs_explorer_path($_REQUEST['file'], 'raw');
$lsd = cs_explorer_path($dir, 'escape');
$red_lsd = cs_explorer_path($dir, 'escape', 1);

$data = array();

if(empty($_POST['submit'])) {

  $target_file = cs_safe_path($cs_main['def_path'], $dir, 1);
  if(empty($dir)) {
    cs_redirect($cs_lang['no_file'], 'explorer', 'roots');
  } elseif($target_file === false OR !file_exists($target_file)) {
    cs_redirect($cs_lang['not_found'] . ': ' . $dir, 'explorer', 'roots');
  } elseif(cs_explorer_denied($target_file)) {
    cs_redirect($cs_lang['not_found'] . ': ' . $dir, 'explorer', 'roots');
  } elseif (!$file = fopen($target_file,'r')) {
    cs_redirect($cs_lang['file_not_opened'], 'explorer', 'roots');
  } else {

    $content = fread($file,filesize($target_file));
    fclose($file);

    $data['if']['phpfile'] = false;

    $data['var']['content'] = cs_secure($content);
    $data['var']['source'] = $dir;
    $data['icn']['unknown'] = cs_html_img('symbols/files/filetypes/unknown.gif', 16, 16);

    echo cs_subtemplate(__FILE__, $data, 'explorer', 'edit');
  }
}
else {

  $target_file = cs_safe_path($cs_main['def_path'], $dir, 1);
  if($target_file === false OR !is_file($target_file))
    die(cs_error_internal(0, 'Invalid target path'));
  if(cs_explorer_denied($target_file))
    die(cs_error_internal(0, 'Access denied'));
  $data = fopen($target_file,'w');
  # set stream encoding if possible to avoid converting issues
  if(function_exists('stream_encoding'))
    stream_encoding($data, $cs_main['charset']);
  $message = fwrite($data,$_POST['data_content']) ? $cs_lang['changes_done'] : $cs_lang['error_edit'];
  fclose($data);

  cs_redirect($message, 'explorer', 'roots', 'dir=' . $red_lsd);
}
