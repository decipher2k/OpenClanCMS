<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('gbook');
$cs_post = cs_post('id');
$cs_get = cs_get('id');

$gbook_id = empty($cs_get['id']) ? $cs_post['id'] : $cs_get['id'];
$from = empty($cs_get['from']) ? $cs_post['from'] : $cs_get['from'];
$from = cs_secure($from, 0, 0, 0, 0, 0);

if($from == 'users') {
  $selid = cs_sql_select(__FILE__,'gbook','gbook_users_id','gbook_id = ?',0,0,1,0,array($gbook_id));
  ...
$gbook = cs_sql_select(__FILE__,'gbook','gbook_nick','gbook_id = ?',0,0,1,0,array($gbook_id));
if(!empty($gbook)) {
  $data = array();
  $data['head']['body'] = sprintf($cs_lang['remove_entry'],$cs_lang['mod_remove'],$gbook['gbook_nick']);
  $data['hidden']['from'] = $from;
  $data['hidden']['id'] = $gbook_id;
  echo cs_subtemplate(__FILE__,$data,'gbook','remove');
}
else {
  cs_redirect('','gbook');
}