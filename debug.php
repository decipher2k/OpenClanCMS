<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

# Overwrite global settings by using the following array
$cs_main = array('init_sql' => true, 'init_tpl' => true, 'tpl_file' => 'debug.htm', 'debug' => true, 'themebar' => true);

require_once 'system/core/functions.php';

cs_init($cs_main);

if(empty($account['access_clansphere']) OR $account['access_clansphere'] < 5)
  die(cs_error_internal(0, 'Access denied'));
