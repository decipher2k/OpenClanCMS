<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

# Overwrite global settings by using the following array
$cs_main = array('init_sql' => true, 'init_tpl' => false, 'init_mod' => true);

chdir('../../');

require_once 'system/core/functions.php';

cs_init($cs_main);

require 'mods/categories/functions.php';

$mod = empty($_GET['mod']) ? '' : $_GET['mod'];
if(!preg_match("=^[a-z0-9_-]+$=i", $mod))
  die(cs_error_internal(0, 'Invalid module'));

echo cs_categories_dropdown2($mod,0,0);
