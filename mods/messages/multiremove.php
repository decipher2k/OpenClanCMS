<?php
// OpenClanCMS 2010 - www.clansphere.net 
// $Id$

$cs_lang = cs_translate('messages');

$data = array();
$cs_get = cs_get('confirm,cancel');

$outbox = (empty($_GET['outbox']) AND empty($_POST['outbox'])) ? 'inbox' : 'outbox';

if(isset($cs_get['confirm'])) {

  $query = $outbox == 'inbox' ? 
    'UPDATE {pre}_messages SET messages_show_receiver = \'0\', messages_archiv_receiver = \'0\' WHERE users_id_to = \''.$account['users_id'].'\' AND (' :
    'UPDATE {pre}_messages SET messages_show_sender = \'0\', messages_archiv_sender = \'0\' WHERE users_id = \''.$account['users_id'].'\' AND (';

  $values = empty($_GET['ids']) ? array() : explode('-',$_GET['ids']);
  $ids = array();
  foreach($values AS $value) {
    $id = (int) $value;
    if($id > 0)
      $ids[] = $id;
  }
  $count_values = count($ids);

  if(empty($count_values))
    cs_redirect($cs_lang['no_selection'],'messages',$outbox);

  for ($run = 0; $run < $count_values; $run++) {
    if ($run != 0)
      $query .= ' OR ';
    $query .= 'messages_id = \'' . $ids[$run] . '\'';
  }
  $query .= ')';

  cs_sql_query(__FILE__,$query);
  cs_sql_query(__FILE__,"DELETE FROM {pre}_messages WHERE messages_show_sender = '0' AND messages_archiv_sender = '0' AND messages_show_receiver = '0' AND messages_archiv_receiver = '0'");

  cs_redirect($cs_lang['del_true'],'messages',$outbox);
} 
elseif(isset($cs_get['cancel'])) {
  cs_redirect($cs_lang['del_false'],'messages',$outbox);
}
else {

  $values = $_POST;
  $ids = array();
  foreach ($values AS $key => $value) {
    if (strpos($key,'select_') === false)
      continue;
    $id = (int) substr($key,7);
    if($id > 0)
      $ids[] = $id;
  }

  if (empty($ids)) {
    cs_redirect($cs_lang['no_selection'],'messages',$outbox);
  }
  else {
    $ids = implode('-',$ids);
    $addout = $outbox == 'outbox' ? '&amp;outbox=outbox' : '';

    $data['content']['head'] = $cs_lang['really_remove_selected'];
    $data['content']['bottom']  = cs_link($cs_lang['confirm'],'messages','multiremove','ids='.$ids.'&amp;confirm' . $addout);
    $data['content']['bottom'] .= ' - ';
    $data['content']['bottom'] .= cs_link($cs_lang['cancel'],'messages','multiremove','cancel' . $addout);
  }
}

echo cs_subtemplate(__FILE__,$data,'messages','multiremove');
