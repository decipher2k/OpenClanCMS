<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$start = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
$cs_sort[1] = 'articles_time DESC';
$cs_sort[2] = 'articles_time ASC';
$cs_sort[3] = 'articles_headline DESC';
$cs_sort[4] = 'articles_headline ASC';
$sort = empty($_REQUEST['sort']) ? 2 : $_REQUEST['sort'];
$order = $cs_sort[$sort];

$where1 = $data['search']['where'] .'&text='. $data['search']['text'] .'&submit=1';

$results = explode(',' ,$data['search']['text']);
$recount = count($results);

$sql_where_params = array();
$sql_where = "(";
for($prerun=0; $prerun<$recount; $prerun++) {
  if($prerun > 0) $sql_where .= ' OR ';
  $sql_where .= "articles_headline LIKE ? OR articles_text LIKE ?";
  $sql_where_params[] = '%' . trim($results[$prerun]) . '%';
  $sql_where_params[] = '%' . trim($results[$prerun]) . '%';
}
$sql_where .= ') AND cat.categories_access <= ?';
$sql_where_params[] = $account['access_articles'];
$select = 'articles_headline, articles_time, articles_id';
$tables = 'articles art INNER JOIN {pre}_categories cat ON art.categories_id = cat.categories_id';
$cs_search = cs_sql_select(__FILE__,$tables,$select,$sql_where,$order,$start,$account['users_limit'],0,$sql_where_params);
$search_loop = count($cs_search);

$data2 = array();
$data2['if']['result'] = false;
$data2['if']['access'] = false;
$data2['if']['noresults'] = false;

if (!empty($search_loop)) {
  $data2['if']['result'] = true;
  $data2['result']['count'] = $search_loop;
  $data2['result']['pages'] = cs_pages('search','list',$search_loop,$start,$where1,$sort);
  $data2['sort']['headline'] = cs_sort('search','list',$start,$where1,3,$sort);
  $data2['sort']['date'] = cs_sort('search','list',$start,$where1,1,$sort);

  for($run=0; $run<$search_loop; $run++) {
      $cs_articles_headline = cs_secure($cs_search[$run]['articles_headline']);
      $data2['results'][$run]['headline'] = cs_link(cs_secure($cs_articles_headline),'articles','view','id=' . $cs_search[$run]['articles_id']);
    $data2['results'][$run]['date'] = cs_date('unix',$cs_search[$run]['articles_time'],1);
  }
} else {
$data2['if']['noresults'] = true;
}
echo cs_subtemplate(__FILE__,$data2,'search','mods_articles');