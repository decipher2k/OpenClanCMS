<?php
// ClanSphere 2010 - www.clansphere.net 
// $Id$

$cs_lang = cs_translate('shoutbox');

$data = array();
$cs_get = cs_get('confirm,ids');

if(isset($cs_get['confirm'])) {
  $values = empty($_GET['ids']) ? array() : explode('-',$_GET['ids']);
  $ids = array();
  foreach($values AS $value) {
    $id = (int) $value;
    if($id > 0)
      $ids[] = $id;
  }
  
  $query_params = array();
  $query = 'DELETE FROM {pre}_shoutbox WHERE ';
  $count_values = count($ids);
  if(empty($count_values))
    cs_redirect($cs_lang['no_selection'],'shoutbox','manage');

  $clauses = array();
  foreach($ids AS $id_val) {
    $clauses[] = 'shoutbox_id = ?';
    $query_params[] = $id_val;
  }
  $query .= implode(' OR ', $clauses);
  cs_sql_query(__FILE__,$query,0,$query_params);
  
  cs_redirect($cs_lang['del_true'],'shoutbox','manage');
}
else {
  $values = $_POST;
  $ids = array();
  
  foreach($values AS $key => $value) {
    if(strpos($key,'select_') === false) { continue; }
    $id = (int) substr($key,7);
    if($id > 0)
      $ids[] = $id;
  }

  if(empty($ids)) { 
  cs_redirect($cs_lang['no_selection'],'shoutbox','manage');
  }
  else { 
    $ids = implode('-',$ids);
    
    $data['content']['head'] = $cs_lang['really_remove_selected'];
    $data['content']['bottom']  = cs_link($cs_lang['confirm'],'shoutbox','multiremove','ids='.$ids.'&amp;confirm');
    $data['content']['bottom'] .= ' - ';
    $data['content']['bottom'] .= cs_link($cs_lang['cancel'],'shoutbox','remove','&amp;cancel');
  }
}

echo cs_subtemplate(__FILE__,$data,'shoutbox','remove');
