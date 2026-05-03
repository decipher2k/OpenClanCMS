<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

$encoded = empty($_GET['mail']) ? '' : $_GET['mail'];
$string = base64_decode($encoded, true);
$pattern = "=^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z](-?[0-9a-z])*\.)+[a-z]{2}([zmuvtg]|fo|me)?\z=i";
if($string !== false AND preg_match($pattern,$string)) {
  echo $string;
}
