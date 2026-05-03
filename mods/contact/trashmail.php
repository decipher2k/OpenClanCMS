<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

function cs_trashmail($email) {

  $parts = explode('@', $email, 2);
  if(empty($parts[1]))
    return false;
  else {
    $where = 'trashmail_entry = ?';
    $check = cs_sql_count(__FILE__, 'trashmail', $where,0,array(strtolower($parts[1])));
    return (empty($check)) ? false : true;
  }
}