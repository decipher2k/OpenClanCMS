<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('modules');

$data = array();

if (empty($_POST['submit'])) {

  $dir = empty($_GET['dir']) ? '' : $_GET['dir'];
  if(!preg_match("=^[a-z0-9_-]+$=i", $dir))
    cs_redirect('', 'modules', 'roots');

  $data['form']['url'] = cs_url('modules','accessedit');
  $data['lang']['edit_access'] = sprintf($cs_lang['edit_access'],$dir);
  $data['value']['dir'] = $dir;

  $cells = 'access_id, access_name, access_clansphere, access_'.$dir;
  $data['access'] = cs_sql_select(__FILE__,'access',$cells,0,'access_clansphere ASC',0,0,0,array());

  $sel = 'selected="selected"';
  $count_access = count($data['access']);

  for ($run = 0; $run < $count_access; $run++) {

    $data['access'][$run]['sel0'] = '';
    $data['access'][$run]['sel1'] = '';
    $data['access'][$run]['sel2'] = '';
    $data['access'][$run]['sel3'] = '';
    $data['access'][$run]['sel4'] = '';
    $data['access'][$run]['sel5'] = '';

    if (isset($_GET['activate'])) {
      $data['access'][$run]['sel'.$data['access'][$run]['access_clansphere']] = $sel;
    } else {
      $data['access'][$run]['sel'.$data['access'][$run]['access_'.$dir]] = $sel; 
    }

  }

  echo cs_subtemplate(__FILE__,$data,'modules','accessedit_1');
  
} else {

  $dir = empty($_POST['dir']) ? '' : $_POST['dir'];
  if(!preg_match("=^[a-z0-9_-]+$=i", $dir))
    cs_redirect('', 'modules', 'roots');

  $access = cs_sql_select(__FILE__,'access','access_id',0,'access_clansphere ASC',0,0,0,array());

  $cells = array('access_'.$dir);

  foreach ($access as $level) {
    $access_value = isset($_POST['access_'.$level['access_id']]) ? (int) $_POST['access_'.$level['access_id']] : 0;
    $access_value = ($access_value < 0 OR $access_value > 5) ? 0 : $access_value;
    $values = array($access_value);
    cs_sql_update(__FILE__,'access',$cells,$values,$level['access_id']);

    cs_cache_delete('access_' . $level['access_id']);
  }

  $data['url']['continue'] = cs_url('modules','roots');

  echo cs_subtemplate(__FILE__,$data,'modules','accessedit_2');
}
