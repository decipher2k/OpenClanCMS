<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('explorer');

include_once 'mods/explorer/functions.php';

$dir = cs_explorer_path($_REQUEST['file'], 'raw');
$lsd = cs_explorer_path($dir, 'escape');
$red_lsd = cs_explorer_path($dir, 'escape', 1);

if(empty($_POST['submit']) && empty($_POST['cancel'])) {

  $target_file = cs_safe_path($cs_main['def_path'], $dir, 1);
  if(empty($dir)) {
    cs_redirect($cs_lang['no_file'], 'explorer','roots') ;
  } elseif($target_file === false OR !file_exists($target_file)) {
    cs_redirect($cs_lang['not_found'] . ': ' . $dir, 'explorer','roots') ;
  } else {

    $data = array();
    $data['lang']['really_delete'] = sprintf($cs_lang['really_delete'],$dir);
    $data['var']['source'] = $lsd;
    
    echo cs_subtemplate(__FILE__, $data, 'explorer', 'remove');
  }

} elseif (!empty($_POST['cancel'])) {

  cs_redirect($cs_lang['del_false'], 'explorer','roots','dir=' . $red_lsd) ;
  
} else {
  
  $target_file = cs_safe_path($cs_main['def_path'], $dir, 1);
  if($target_file === false OR $target_file == realpath($cs_main['def_path']))
    die(cs_error_internal(0, 'Invalid target path'));
  if (is_dir($target_file)) {

    cs_remove_dir($dir);
    $message = !is_dir($target_file) ? $cs_lang['dir_removed'] : $cs_lang['dir_error'];
    
  } else { 
    $message = unlink($target_file) ? $cs_lang['file_removed'] : $cs_lang['file_remove_error'];
  }
  
  cs_redirect($message, 'explorer','roots','dir=' . $red_lsd);
}
