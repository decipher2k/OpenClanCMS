<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('linkus');
$cs_get = cs_get('id,agree,cancel');

if(isset($cs_get['agree'])) {
  $linkus = cs_sql_select(__FILE__,'linkus','linkus_banner','linkus_id = ?',0,0,1,0,array($cs_get['id']));
  ...
$linkus = cs_sql_select(__FILE__,'linkus','linkus_name','linkus_id = ?',0,0,1,0,array($cs_get['id']));
if(!empty($linkus)) {
  $data = array();
  $data['head']['body'] = sprintf($cs_lang['remove_entry'],$cs_lang['mod_name'],$linkus['linkus_name']);
  $data['url']['agree'] = cs_url('linkus','remove','id=' . $cs_get['id'] . '&amp;agree');
  $data['url']['cancel'] = cs_url('linkus','remove','id=' . $cs_get['id'] . '&amp;cancel');
  echo cs_subtemplate(__FILE__,$data,'linkus','remove');
}
else {
  cs_redirect('','linkus');
}