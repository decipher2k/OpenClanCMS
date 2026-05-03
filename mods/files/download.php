<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('files');

$advanced = 0;

if(empty($_REQUEST['where']))
  cs_redirect(NULL, 'files', 'list');

$files_id = $_REQUEST['where'];
settype($files_id,'integer');
$mirror_id = isset($_REQUEST['target']) ? $_REQUEST['target'] : 0;
settype($mirror_id,'integer');

$from = 'files';
$select = 'files_count, files_name, files_mirror';
$where = 'files_id = ?'; 
$cs_files = cs_sql_select(__FILE__,$from,$select,$where,0,0,1,0,array($files_id)); 
$files_loop = !empty($cs_files) && is_array($cs_files) ? count($cs_files) : 0;

if(!empty($files_loop)) {
  $files_count = $cs_files['files_count'];
  $files_mirror = $cs_files['files_mirror']; 

  $temp_mirror1 = explode("-----", $files_mirror);
  if($mirror_id < 0 OR !isset($temp_mirror1[$mirror_id]))
    cs_redirect(NULL, 'files', 'view', 'where=' . $files_id);
  $mirror = explode("\n", $temp_mirror1[$mirror_id]); 

  $files_count = $files_count + 1;
  $files_cells = array('files_count');
  $files_save = array($files_count);
  cs_sql_update(__FILE__,'files',$files_cells,$files_save,$files_id);

  if(empty($advanced) AND isset($mirror[1]))
  {
    $location = trim($mirror[1]);
    if(preg_match("=^https?://[^\r\n]+$=i", $location))
      header('Location: ' . $location);
    else
      cs_redirect(NULL, 'files', 'view', 'where=' . $files_id);
  }  
  elseif(!empty($advanced))
  {
    $data['file']['wait'] = cs_html_img('symbols/files/wait.gif',0,0,0); 

    /*print("<meta http-equiv=refresh content='3; URL=$downloadfile'>");
    header("Content-disposition: attachment; filename = $downloadfile");
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    header("Pragma: no-cache");
    header("Expires: 0");*/
  }
}
else {
  include_once 'mods/errors/404.php';
}
