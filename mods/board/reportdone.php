<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('board');

$cs_get = cs_get('id');
$report_id = empty($cs_get['id']) ? 0 : $cs_get['id'];
if($report_id < 1)
  cs_redirect($cs_lang['del_false'],'board','reportlist');

$report_cells = array('boardreport_done');
$report_save = array(1);
cs_sql_update(__FILE__,'boardreport',$report_cells,$report_save,$report_id);

cs_cache_delete('count_boardreport');

cs_redirect($cs_lang['done_true'],'board','reportlist');
