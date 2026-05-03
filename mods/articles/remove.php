<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

$cs_lang = cs_translate('articles');
$cs_get = cs_get('id');
$cs_post = cs_post('id');
$articles_id = empty($cs_get['id']) ? $cs_post['id'] : $cs_get['id'];

if(isset($cs_post['agree'])) {
  cs_sql_delete(__FILE__,'articles',$articles_id);
  $query = "DELETE FROM {pre}_comments WHERE comments_mod='articles' AND comments_fid = ?";
  cs_sql_query(__FILE__,$query,0,array($articles_id));

  require_once 'mods/pictures/functions.php';
  cs_pictures_delete($articles_id, 'articles');

  cs_redirect($cs_lang['del_true'], 'articles');
}

if(isset($cs_post['cancel']))  {
  cs_redirect($cs_lang['del_false'], 'articles');
}

$article = cs_sql_select(__FILE__,'articles','articles_headline','articles_id = ?',0,0,1,0,array($articles_id));
if(!empty($article)) {
  $data['head']['body'] = sprintf($cs_lang['remove_entry'],$cs_lang['mod_name'],$article['articles_headline']);
  $data['articles']['id'] = $articles_id;
  echo cs_subtemplate(__FILE__,$data,'articles','remove');
}
else {
  cs_redirect('','articles');
}
