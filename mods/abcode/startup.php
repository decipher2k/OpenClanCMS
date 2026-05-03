<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

global $cs_main;

$op_abcode = cs_sql_option(__FILE__, 'abcode');

if(!function_exists('cs_abcode_rte_allowed')) {
  function cs_abcode_rte_allowed($dir, $show) {

    $dir = (string) $dir;
    if(empty($dir) OR !preg_match("=^[_a-z0-9-]+$=i", $dir))
      return '';

    $rte_dirs = cs_checkdirs('mods', $show);
    foreach($rte_dirs AS $rte)
      if($rte['dir'] == $dir AND file_exists('mods/' . $dir . '/rte_init.php'))
        return $dir;

    return '';
  }
}

$op_abcode['rte_html'] = cs_abcode_rte_allowed($op_abcode['rte_html'], 'abcode/rte_html');
$op_abcode['rte_more'] = cs_abcode_rte_allowed($op_abcode['rte_more'], 'abcode/rte_more');

$cs_main['rte_html'] = $op_abcode['rte_html'];
$cs_main['rte_more'] = $op_abcode['rte_more'];

if(!empty($op_abcode['rte_html']))
  include_once 'mods/' . $op_abcode['rte_html'] . '/rte_init.php';

if(!empty($op_abcode['rte_more']) AND $op_abcode['rte_more'] != $op_abcode['rte_html'])
  include_once 'mods/' . $op_abcode['rte_more'] . '/rte_init.php';
