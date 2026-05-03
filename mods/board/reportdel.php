<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('board');

$cs_get = cs_get('id,agree,cancel');
$report_id = empty($cs_get['id']) ? 0 : $cs_get['id'];

if($report_id < 1)
  cs_redirect($cs_lang['del_false'],'board','reportlist');

if(isset($cs_get['agree'])) {

  cs_sql_delete(__FILE__,'boardreport',$report_id);
  
  cs_cache_delete('count_boardreport');

  cs_redirect($cs_lang['del_true'],'board','reportlist');
}
elseif(isset($cs_get['cancel'])) {
  cs_redirect($cs_lang['del_false'],'board','reportlist');
}
else {
  $data['head']['topline'] = sprintf($cs_lang['remove_rly'],$report_id);
  $data['boardreport']['content'] = cs_link($cs_lang['confirm'],'board','reportdel','id=' . $report_id . '&amp;agree');
  $data['boardreport']['content'] .= ' - ';
  $data['boardreport']['content'] .= cs_link($cs_lang['cancel'],'board','reportdel','id=' . $report_id . '&amp;cancel');
}

echo cs_subtemplate(__FILE__,$data,'board','reportdel');
