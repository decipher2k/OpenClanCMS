<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('messages');

$cs_get = cs_get('id');
$messages_id = empty($cs_get['id']) ? 0 : $cs_get['id'];

$messages_form = 1;
$error = 0;

$users_id = $account['users_id'];
$from = 'messages';
$select = 'users_id,users_id_to';
$where = "messages_id = '" . $messages_id . "' AND (users_id = '" . $users_id . "' OR users_id_to = '" . $users_id . "')";
$cs_messages = cs_sql_select(__FILE__,$from,$select,$where);
if(empty($cs_messages))
  cs_redirect($cs_lang['del_false'],'messages','center');

$messages_users_id = $cs_messages['users_id'];
$messages_users_id_2 = $cs_messages['users_id_to'];
$archivbox_count = cs_sql_count(__FILE__,'messages','(users_id = ? AND messages_archiv_sender = ?) OR (users_id_to = ? AND messages_archiv_receiver = ?)',0,array($users_id, '1', $users_id, '1'));
$cs_messages_option = cs_sql_option(__FILE__,'messages');
$max_space = $cs_messages_option['max_space'];

if($archivbox_count >= $max_space)
{
  $error++;
}
if(empty($error))
{
  if($messages_users_id == $users_id)
  {
    $messages_archiv_sender = '1';
    $messages_show_sender = '0';
    $messages_cells = array('messages_show_sender','messages_archiv_sender');
    $messages_content = array($messages_show_sender,$messages_archiv_sender);
    cs_sql_update(__FILE__,'messages',$messages_cells,$messages_content,$messages_id);
  }
  if($messages_users_id_2 == $users_id)
  {
    $messages_archiv_receiver = '1';
    $messages_show_receiver = '0';
    $messages_cells = array('messages_show_receiver','messages_archiv_receiver');
    $messages_content = array($messages_show_receiver,$messages_archiv_receiver);
    cs_sql_update(__FILE__,'messages',$messages_cells,$messages_content,$messages_id);
  }
  
  cs_redirect($cs_lang['arch_true'],'messages','center');
}
else
{

  
  cs_redirect(cs_icon('important') . ' ' . $cs_lang['arch_max'],'messages','center');
}
