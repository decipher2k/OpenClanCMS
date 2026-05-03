<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('board');

$abo_form = 1;
$abo_id = $_REQUEST['id'];
settype($abo_id,'integer');

if(isset($_POST['agree'])) {
  $abonements_form = 0;
  $select = 'users_id';
$computer = cs_sql_select(__FILE__,'abonements',$select,'abonements_id = ?',0,0,1,0,array($abo_id));
...
$search_user = cs_sql_select(__FILE__,'abonements','users_id','abonements_id = ?',0,0,1,0,array($abo_id));
  
  if($search_user['users_id'] == $account['users_id'] OR $account['access_board'] >= 5) {
    $data['if']['not_account'] = false;
    $data['if']['account'] = true;
  
    $data['lang']['body'] = sprintf($cs_lang['del_rly'],$abo_id);
    $data['action']['form'] = cs_url('board','delabo');
    $data['abo']['id'] = $abo_id;
  }
  else {
    $data['if']['not_account'] = true;
    $data['if']['account'] = false;
  }
  
  echo cs_subtemplate(__FILE__,$data,'board','delabo');
}