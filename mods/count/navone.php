<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$where = 'count_time > ?';
echo number_format(cs_sql_count(__FILE__,'count',$where,0,array(cs_time() - 300)),0,',','.');