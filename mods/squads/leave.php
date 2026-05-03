<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('squads');
$cs_post = cs_post('id');

$op_squads = cs_sql_option(__FILE__,'squads');
$label = $op_squads['label'];

if(isset($_POST['agree'])) {
  $squads_id = $cs_post['id'];
  $getme = cs_sql_select(__FILE__,'members','members_id','squads_id = ? AND users_id = ?',0,0,1,0,array($squads_id, $account['users_id']));
  cs_sql_delete(__FILE__,'members',$getme['members_id']);

  cs_redirect($cs_lang['sq_del_true'],'squads','center');
}

if(isset($_POST['cancel'])) {
  cs_redirect($cs_lang['del_false'],'squads','center');
}
else {
   $data['head']['mod'] = $cs_lang[$label.'s'];
   $data['lang']['label'] = $cs_lang[$label];

  $sqd_data = cs_sql_select(__FILE__,$from,$select,'mem.users_id = ?','sqd.squads_name',0,0,0,array($account['users_id']));
  $data['squads']['squad_sel'] = cs_dropdown('id','squads_name',$sqd_data,0,'squads_id');
    
  echo cs_subtemplate(__FILE__,$data,'squads','leave');
}