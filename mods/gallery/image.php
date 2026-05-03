<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

# Overwrite global settings by using the following array
$cs_main = array('init_sql' => true, 'init_tpl' => false, 'init_mod' => true);

chdir('../../');

require_once 'system/core/functions.php';

cs_init($cs_main);

chdir('mods/gallery/');

global $cs_main, $account;

function cs_gallery_image_path($base, $name) {

  $safe_name = cs_safe_filename($name);
  if($safe_name === false)
    die(cs_error_internal(0, 'Invalid image name'));

  $path = cs_safe_path($base, $safe_name, 1);
  if($path === false OR !is_file($path))
    die(cs_error_internal(0, 'Image not found'));

  return $path;
}

function cs_gallery_image_size($size, $default = 500, $max = 2000) {

  $size = (int) $size;
  if($size < 1)
    return $default;
  return $size > $max ? $max : $size;
}

function cs_gallery_image_rotate($rotate) {

  $rotate = (int) $rotate;
  return ($rotate < -360 OR $rotate > 360) ? 0 : $rotate;
}

function cs_gallery_user_image($id) {

  global $account;

  $id = (int) $id;
  if($id < 1)
    die(cs_error_internal(0, 'Image not found'));

  $from = 'usersgallery ugy INNER JOIN {pre}_folders fol ON ugy.folders_id = fol.folders_id';
  $select  = 'ugy.usersgallery_name AS usersgallery_name, ugy.usersgallery_time AS usersgallery_time, ';
  $select .= 'ugy.usersgallery_count AS usersgallery_count, ';
  $select .= 'ugy.usersgallery_count_downloads AS usersgallery_count_downloads, ';
  $select .= 'ugy.usersgallery_status AS usersgallery_status, ';
  $select .= 'ugy.usersgallery_access AS usersgallery_access, ugy.users_id AS users_id, ';
  $select .= 'fol.folders_access AS folders_access';
  $where = "ugy.usersgallery_id = " . $id . " AND fol.folders_mod = 'usersgallery'";
  $cs_gallery = cs_sql_select(__FILE__,$from,$select,$where);

  if(empty($cs_gallery))
    die(cs_error_internal(0, 'Image not found'));

  $owner = !empty($account['users_id']) AND $account['users_id'] == $cs_gallery['users_id'];
  $admin = $account['access_usersgallery'] >= 4;
  $visible = !empty($cs_gallery['usersgallery_status'])
          AND $account['access_usersgallery'] >= $cs_gallery['usersgallery_access']
          AND $account['access_usersgallery'] >= $cs_gallery['folders_access'];

  if(empty($owner) AND empty($admin) AND empty($visible))
    die(cs_error_internal(0, 'Access denied'));

  return $cs_gallery;
}

function cs_gallery_board_image($name) {

  global $account;

  $safe_name = cs_safe_filename($name);
  if($safe_name === false)
    die(cs_error_internal(0, 'Image not found'));

  $ext = strtolower(substr(strrchr($safe_name, '.'), 1));
  $allowed = array('gif' => 1, 'jpg' => 1, 'jpeg' => 1, 'png' => 1);
  if(empty($allowed[$ext]))
    die(cs_error_internal(0, 'Image not found'));

  $tables  = 'boardfiles fil INNER JOIN {pre}_threads thr ON thr.threads_id = fil.threads_id ';
  $tables .= 'INNER JOIN {pre}_board brd ON brd.board_id = thr.board_id ';
  $tables .= 'LEFT OUTER JOIN {pre}_boardpws bpw ON bpw.board_id = brd.board_id ';
  $tables .= 'AND bpw.users_id = ' . (int) $account['users_id'];
  $select  = 'fil.boardfiles_id AS boardfiles_id, fil.boardfiles_name AS boardfiles_name, ';
  $select .= 'brd.board_access AS board_access, brd.squads_id AS squads_id, ';
  $select .= 'brd.board_pwd AS board_pwd, bpw.boardpws_id AS boardpws_id';

  if(preg_match('=^([1-9][0-9]*)\.[a-z0-9]+$=i', $safe_name, $match))
    $where = 'fil.boardfiles_id = ?';
  else
    $where = 'fil.boardfiles_name = ?';
  $where_params = array(preg_match('=^([1-9][0-9]*)\.[a-z0-9]+$=i', $safe_name, $match) ? $match[1] : $safe_name);

  $cs_file = cs_sql_select(__FILE__,$tables,$select,$where,0,0,1,0,$where_params);
  if(empty($cs_file))
    die(cs_error_internal(0, 'Image not found'));

  if(!empty($cs_file['squads_id']) AND $account['access_board'] < $cs_file['board_access']) {
    $sq_where = 'users_id = ' . (int) $account['users_id'] . ' AND squads_id = ' . (int) $cs_file['squads_id'];
    $check_sq = cs_sql_count(__FILE__,'members',$sq_where);
    if(empty($check_sq))
      die(cs_error_internal(0, 'Access denied'));
  }
  elseif($account['access_board'] < $cs_file['board_access']) {
    die(cs_error_internal(0, 'Access denied'));
  }

  if(!empty($cs_file['board_pwd']) AND empty($cs_file['boardpws_id']))
    die(cs_error_internal(0, 'Access denied'));

  $file = $cs_file['boardfiles_name'];
  $file_ext = substr(strrchr($file, '.'), 1);
  if(empty($allowed[strtolower($file_ext)]))
    die(cs_error_internal(0, 'Image not found'));

  $stored_name = $cs_file['boardfiles_id'] . '.' . $file_ext;
  $path = cs_safe_path("../../uploads/board/files", $stored_name, 1);
  if($path === false OR !is_file($path)) {
    $legacy_name = cs_safe_filename($file);
    $path = $legacy_name === false ? false : cs_safe_path("../../uploads/board/files", $legacy_name, 1);
  }

  if($path === false OR !is_file($path))
    die(cs_error_internal(0, 'Image not found'));

  return $path;
}

if(!empty($_REQUEST['pic']) OR !empty($_REQUEST['thumb']))
{
  $options = cs_sql_option(__FILE__, 'gallery');
  
  if(!empty($_REQUEST['pic'])) {
    $gallery_id = (int) $_REQUEST['pic'];
  }
  elseif(!empty($_REQUEST['thumb']))
  {
    $gallery_id = (int) $_REQUEST['thumb'];
  }
  if($gallery_id < 1)
    die(cs_error_internal(0, 'Image not found'));

  $from = 'gallery gal INNER JOIN {pre}_folders fol ON gal.folders_id = fol.folders_id';
  $select  = 'gal.gallery_watermark AS gallery_watermark, ';
  $select .= 'gal.gallery_watermark_pos AS gallery_watermark_pos, ';
  $select .= 'gal.gallery_name AS gallery_name, gal.gallery_time AS gallery_time, ';
  $select .= 'gal.gallery_count AS gallery_count, ';
  $select .= 'gal.gallery_count_downloads AS gallery_count_downloads, ';
  $select .= 'gal.gallery_access AS gallery_access, gal.gallery_status AS gallery_status, ';
  $select .= 'gal.users_id AS users_id, fol.folders_access AS folders_access';
  $where = "gal.gallery_id = '" . $gallery_id . "' AND fol.folders_mod = 'gallery'";
  $cs_gallery = cs_sql_select(__FILE__,$from,$select,$where);
  if(empty($cs_gallery))
    die(cs_error_internal(0, 'Image not found'));

  $gallery_loop = count($cs_gallery);
  
  $owner = !empty($account['users_id']) AND $account['users_id'] == $cs_gallery['users_id'];
  $admin = $account['access_gallery'] >= 4;
  $visible = !empty($cs_gallery['gallery_status'])
          AND $account['access_gallery'] >= $cs_gallery['gallery_access']
          AND $account['access_gallery'] >= $cs_gallery['folders_access'];

  if (empty($owner) AND empty($admin) AND empty($visible)) {
    die(cs_error_internal(0, 'Access denied'));
  }

  $position = $cs_gallery['gallery_watermark_pos'];
  $temp_pos = empty($position) ? array(0,1) : explode("|--@--|", $position);
  $position = $temp_pos[0];
  $transparenz = $temp_pos[1];
  $name = $cs_gallery['gallery_name'];
  $gallery_time = $cs_gallery['gallery_time'];
  $gallery_count = $cs_gallery['gallery_count'];
  $gallery_count_downloads = $cs_gallery['gallery_count_downloads'];

  if(!empty($_REQUEST['pic']))
  {
    $gallery_count = $gallery_count + 1;
    $gallery_cells = array('gallery_count');
    $gallery_save = array($gallery_count);
    cs_sql_update(__FILE__,'gallery',$gallery_cells,$gallery_save,$gallery_id);
  }
}
if(!empty($_REQUEST['usersthumb'])) {
  $cs_gallery = cs_gallery_user_image($_REQUEST['usersthumb']);
  $gallery_loop = count($cs_gallery);

  $name = $cs_gallery['usersgallery_name'];
  $gallery_time = $cs_gallery['usersgallery_time'];
  $gallery_count = $cs_gallery['usersgallery_count'];
  $gallery_count_downloads = $cs_gallery['usersgallery_count_downloads'];
}

if(!empty($_REQUEST['userspic'])) {
  $cs_gallery = cs_gallery_user_image($_REQUEST['userspic']);
  $gallery_loop = count($cs_gallery);

  $name = $cs_gallery['usersgallery_name'];
  $gallery_time = $cs_gallery['usersgallery_time'];
  $gallery_count = $cs_gallery['usersgallery_count'];
  $gallery_count_downloads = $cs_gallery['usersgallery_count_downloads'];

    $gallery_count = $gallery_count + 1;
  $gallery_cells = array('usersgallery_count');
  $gallery_save = array($gallery_count);
  cs_sql_update(__FILE__,'usersgallery',$gallery_cells,$gallery_save,(int) $_REQUEST['userspic']);
}

class PictureEngine
{
  var $image;
  var $width;
  var $height;
  var $Transformation;

  function __construct(&$image)
  {
    $this->data($image);
    $this->Transformation = new Transformation($this->image);
  }

  function data(&$image)
  {
    $this->image = &$image;
    $this->width = imagesx($this->image);
    $this->height = imagesy($this->image);
  }

  function Dump()
  {
    header("Content-type: image/jpeg");
    Imagejpeg($this->image,null,100);
  }

  function Dump_down($count, $table = 'gallery', $id = 0)
  {
    $table = $table == 'usersgallery' ? 'usersgallery' : 'gallery';
    $cell = $table == 'usersgallery' ? 'usersgallery_count_downloads' : 'gallery_count_downloads';
    $gallery_count_downloads = $count + 1;
    $gallery_cells = array($cell);
    $gallery_save = array($gallery_count_downloads);
    cs_sql_update(__FILE__,$table,$gallery_cells,$gallery_save,(int) $id);

    # disable browser / proxy caching
    header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    header('Content-Transfer-Encoding: none');
    header("Accept-Ranges: bytes");
    header("Content-type: image/jpg");
    header("Content-Disposition: attachment; filename=image.jpg");
    Imagejpeg($this->image,null,100);
    exit;
  }
}

class Transformation extends PictureEngine
{

  function __construct (&$image)
  {
    $this->data($image);
  }

  function Scale($maxwidth = 500, $maxheight = 10000)
  {
    $this->data($this->image);
    $xFactor = $this->width / $maxwidth;
    $yFactor = $this->height / $maxheight;

    if ($xFactor > $yFactor)
    {
      $newwidth = $maxwidth;
      $newheight = $this->height / $xFactor;
    }
    else
    {
      $newwidth = $this->width / $yFactor;
      $newheight = $maxheight;
    }

    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $this->image, 0, 0, 0, 0, $newwidth, $newheight, $this->width, $this->height);
    imagedestroy($this->image);
    $this->image = $dst;
    $this->data($this->image);
  }

  function watermark($watermark,$transparenz = 20,$filename,$position)
  {
    $this->data($this->image);
    $transparenz = 100 - $transparenz;
    if($position < 1 || $position > 9)
        return FALSE;

    $info_watermark = getimagesize($watermark);
    $dst = imagecreatetruecolor($this->width, $this->height);
    switch($info_watermark[2])
    {
      case 1:
      $watermark = imagecreatefromgif($watermark);
      break;
      case 2:
      $watermark = imagecreatefromjpeg($watermark);
      break;
      case 3:
      $watermark = imagecreatefrompng($watermark);
      break;
      }
    switch (($position-1)%3)
    {
      case 0:
      $pos_x = 0;
      break;
      case 1:
      $pos_x = round(($this->width - $info_watermark[0])/2, 0);
      break;
      case 2:
      $pos_x = $this->width - $info_watermark[0];
      break;
    }
    switch (floor(($position-1)/3))
    {
      case 0:
      $pos_y = 0;
      break;
      case 1:
      $pos_y = round(($this->height - $info_watermark[1])/2, 0);
      break;
      case 2:
      $pos_y = $this->height - $info_watermark[1];
      break;
    }

    imagecolortransparent($watermark, imagecolorat($watermark, 0, 0));
    imagecopymerge($dst, $this->image, 0, 0, 0, 0, $this->width, $this->height,100);
    imagecopymerge($dst, $watermark, $pos_x, $pos_y, 0, 0,$info_watermark[0],$info_watermark[1],$transparenz);
    imagedestroy($this->image);
    $this->image = $dst;
    $this->data($this->image);
  }
  function rotate($degrees=0)
  {
    $this->data($this->image);
    $rotate = imagerotate($this->image, $degrees, 0);
    imagecopyresampled($rotate, $this->image, 0, 0, 0, 0, 0, 0, $this->width, $this->height);
    imagedestroy($this->image);
    $this->image = $rotate;
    $this->data($this->image);
  }
}

if(extension_loaded('gd') AND isset($_REQUEST['pic']))
{
  $pic = cs_gallery_image_path("../../uploads/gallery/pics", $name);
  $img_size = getimagesize($pic);
  switch($img_size[2])
  {
    case 1:
    $pic_created = imagecreatefromgif($pic);
    break;
    case 2:
    $pic_created = imagecreatefromjpeg($pic);
    break;
    case 3:
    $pic_created = imagecreatefrompng($pic);
    break;
    }
    $im = new PictureEngine($pic_created);

  if(!empty($cs_gallery['gallery_watermark']))
  {
    $watermark = cs_gallery_image_path('../../uploads/categories', $cs_gallery['gallery_watermark']);
    $im->Transformation->watermark($watermark,$transparenz,$cs_gallery['gallery_watermark'],$position);
  }
  if(isset($_REQUEST['size']))
  {
    $size = cs_gallery_image_size($_REQUEST['size']);
    $im->Transformation->Scale($size);
  }
  if(isset($_REQUEST['rotate']))
  {
    $rotate = cs_gallery_image_rotate($_REQUEST['rotate']);
    $im->Transformation->rotate($rotate);
  }
  if(isset($_REQUEST['down']))
  {
    $im->dump_down($gallery_count_downloads, 'gallery', (int) $_REQUEST['pic']);
  }
  else
  {
    $im->dump();
  }
}

if(extension_loaded('gd') AND isset($_REQUEST['userspic']))
{
  $pic = cs_gallery_image_path("../../uploads/usersgallery/pics", $name);
  $img_size = getimagesize($pic);
  switch($img_size[2])
  {
    case 1:
    $im = new PictureEngine(imagecreatefromgif($pic));
    break;
    case 2:
    $im = new PictureEngine(imagecreatefromjpeg($pic));
    break;
    case 3:
    $im = new PictureEngine(imagecreatefrompng($pic));
    break;
    }

  if(isset($cs_gallery['gallery_watermark']) AND !empty($cs_gallery['gallery_watermark']))
  {
    $watermark = cs_gallery_image_path('../../uploads/categories', $cs_gallery['gallery_watermark']);
    $im->Transformation->watermark($watermark,$transparenz,$cs_gallery['gallery_watermark'],$position);
  }
  if(isset($_REQUEST['size']))
  {
    $size = cs_gallery_image_size($_REQUEST['size']);
    $im->Transformation->Scale($size);
  }
  if(isset($_REQUEST['rotate']))
  {
    $rotate = cs_gallery_image_rotate($_REQUEST['rotate']);
    $im->Transformation->rotate($rotate);
  }
  if(isset($_REQUEST['down']))
  {
    $im->dump_down($gallery_count_downloads, 'usersgallery', (int) $_REQUEST['userspic']);
  }
  else
  {
    $im->dump();
  }
}

if(!extension_loaded('gd') AND isset($_REQUEST['pic']))
{
  $pic = cs_gallery_image_path("../../uploads/gallery/pics", $name);
  $data = fopen($pic,'r');
  echo fread($data, filesize($pic));
}

if(extension_loaded('gd') AND isset($_REQUEST['thumb']))
{
  $thumb_file = $name;
  $thumb = cs_gallery_image_path("../../uploads/gallery/thumbs", 'Thumb_' . $thumb_file);
  $img_size = getimagesize($thumb);
  switch($img_size[2])
  {
    case 1:
    $im = new PictureEngine(imagecreatefromgif($thumb));
    break;
    case 2:
    $im = new PictureEngine(imagecreatefromjpeg($thumb));
    break;
    case 3:
    $im = new PictureEngine(imagecreatefrompng($thumb));
    break;
    }
  $time = cs_time();
  $time = $time - (60 * 60 * 24);
  if($gallery_time >= $time) {
    $im->Transformation->watermark('../../symbols/gallery/new.png','40','new.png','1');
  }
  $im->Transformation->Scale($options['thumbs'],'100');
  $im->dump();
}

if(!extension_loaded('gd') AND isset($_REQUEST['thumb']))
{
  $thumb_file = $name;
  $thumb = cs_gallery_image_path("../../uploads/gallery/thumbs", 'Thumb_' . $thumb_file);
  $data = fopen($thumb,'r');
  echo fread($data, filesize($thumb));
}

if(extension_loaded('gd') AND isset($_REQUEST['picname']))
{
  if($account['access_gallery'] < 3)
    die(cs_error_internal(0, 'Access denied'));

  $pic_name = $_REQUEST['picname'];
  $pic = cs_gallery_image_path("../../uploads/gallery/pics", $pic_name);
  $img_size = getimagesize($pic);
  switch($img_size[2])
  {
    case 1:
    $im = new PictureEngine(imagecreatefromgif($pic));
    break;
    case 2:
    $im = new PictureEngine(imagecreatefromjpeg($pic));
    break;
    case 3:
    $im = new PictureEngine(imagecreatefrompng($pic));
    break;
    }
  if($im == true)
  {
    $size = '80';
    $im->Transformation->Scale($size,$size);
    $im->dump();
  }
}
if(extension_loaded('gd') AND isset($_REQUEST['boardpic']))
{
  $pic = cs_gallery_board_image($_REQUEST['boardpic']);
  $img_size = getimagesize($pic);
  switch($img_size[2])
  {
    case 1:
    $im = new PictureEngine(imagecreatefromgif($pic));
    break;
    case 2:
    $im = new PictureEngine(imagecreatefromjpeg($pic));
    break;
    case 3:
    $im = new PictureEngine(imagecreatefrompng($pic));
    break;
    }
  if($im == true)
  {
    if(isset($_REQUEST['boardthumb']))
    {
      $size = '80';
      $im->Transformation->Scale($size,$size);
    }
    $im->dump();
  }
}

if(extension_loaded('gd') AND isset($_REQUEST['usersthumb']))
{
  $thumb_file = $name;
  $thumb = cs_gallery_image_path("../../uploads/usersgallery/thumbs", 'Thumb_' . $thumb_file);
  $img_size = getimagesize($thumb);
  switch($img_size[2])
  {
    case 1:
    $im = new PictureEngine(imagecreatefromgif($thumb));
    break;
    case 2:
    $im = new PictureEngine(imagecreatefromjpeg($thumb));
    break;
    case 3:
    $im = new PictureEngine(imagecreatefrompng($thumb));
    break;
    }
  $time = cs_time();
  $time = $time - (60 * 60 * 24);
  if($gallery_time >= $time)
  {
    $im->Transformation->watermark('../../symbols/gallery/new.png','40','new.png','1');
  }
  $im->Transformation->Scale('100','100');
  $im->dump();
}
