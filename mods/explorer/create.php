<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

include_once 'mods/explorer/functions.php';

$cs_lang = cs_translate('explorer');

$target = empty($_REQUEST['dir']) ? '' : $_REQUEST['dir'];
$dir = cs_explorer_path($target, 'raw');
$lsd = cs_explorer_path($dir, 'escape');

$data = array();

if(empty($_POST['submit'])) {

  include_once 'mods/explorer/abcode.php';

  $data['var']['dir'] = $dir;
  
  $data['abcode']['tools'] = cs_abcode_tools('data_content');
  $data['abcode']['html1'] = cs_abcode_toolshtml('data_content');
  $data['abcode']['sql'] = cs_abcode_sql('data_content');
  $data['abcode']['html2'] = cs_abcode_toolshtml2('data_content');

  echo cs_subtemplate(__FILE__, $data, 'explorer', 'create');

}
else {

  $dir = cs_explorer_path($_POST['data_folder'], 'raw');

  if (substr($dir,-1) != '/' && !empty($dir))
    $dir .= '/';

  $filename = empty($_POST['data_name']) ? 'unnamed' : cs_safe_filename($_POST['data_name']);
  if($filename === false)
    die(cs_error_internal(0, 'Invalid file name'));
  $file = $dir . $filename;
  $ending = !empty($_POST['data_type']) ? preg_replace("=[^a-z0-9.]=i", '', $_POST['data_type']) : '';

  if (empty($ending) && strpos($file,'.') !== false) {
    $ending = strtolower(strrchr($file,'.'));
    $endingpos = strlen($file) - strlen($ending);
    $file = substr($file,0,$endingpos);
  }

  $x = 1;
  $file_test = $file;

  while(file_exists($file_test . $ending)) {
    $x++;
    $file_test = $file . $x;
  }

  $file = $file_test . $ending;
  $target_file = cs_safe_path($cs_main['def_path'], $file, 0);
  if($target_file === false)
    die(cs_error_internal(0, 'Invalid target path'));
  if(cs_explorer_denied($target_file))
    die(cs_error_internal(0, 'Access denied'));

  $data = fopen($target_file,'w');
  # set stream encoding if possible to avoid converting issues
  if(function_exists('stream_encoding'))
    stream_encoding($data, $cs_main['charset']);
  fwrite($data,$_POST['data_content']);
  fclose($data);

  $message = is_file($target_file) ? sprintf($cs_lang['file_created'],$file) : $cs_lang['file_error'];
  $red_lsd = cs_explorer_path($dir, 'escape');

  cs_redirect($message, 'explorer','roots','dir='.$red_lsd);
}
