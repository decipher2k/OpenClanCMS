<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('board');

$att_form = 1;
$att_id = $_REQUEST['id'];
settype($att_id,'integer');

if(isset($_POST['agree'])) {
  $att_form = 0;
  $select = 'users_id, boardfiles_name';
$computer = cs_sql_select(__FILE__,'boardfiles',$select,'boardfiles_id = ?',0,0,1,0,array($att_id));
...
$search_user = cs_sql_select(__FILE__,'boardfiles','users_id','boardfiles_id = ?',0,0,1,0,array($att_id));
  
  if($account['access_board'] >= 5) {      
  $data['if']['not_account'] = false;
  $data['if']['account'] = true;
  
  $data['lang']['body'] = sprintf($cs_lang['del_rly'],$att_id);
    $data['action']['form'] = cs_url('board','delatt_admin');
    $data['att']['id'] = $att_id;
  }
  else {
    $data['if']['not_account'] = true;
  $data['if']['account'] = false;
  }
  
  echo cs_subtemplate(__FILE__,$data,'board','delatt');
}