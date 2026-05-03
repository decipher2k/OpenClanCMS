<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

require_once 'mods/ajax/mail_func.php';

function cs_html_mail($mail, $link = '')
{
  return cs_ajax_mail($mail, $link);
}
function cs_html_jabbermail($mail, $link = '')
{
  return cs_ajax_mail($mail, $link);
}

function cs_html_attr($value)
{
  $value = str_replace(array("\r", "\n", "\0"), '', (string) $value);
  $value = preg_replace('/&(?![a-zA-Z][a-zA-Z0-9]+;|#[0-9]+;|#x[0-9a-fA-F]+;)/', '&amp;', $value);
  return str_replace(array('"', "'", '<', '>'), array('&quot;', '&#039;', '&lt;', '&gt;'), $value);
}

function cs_html_uri($url, $allow_javascript = 0)
{
  $url = trim(str_replace(array("\r", "\n", "\0"), '', (string) $url));

  if(preg_match('=^([a-z][a-z0-9+.-]*):=i', $url, $scheme_match)) {
    $scheme = strtolower($scheme_match[1]);
    $allowed = array(
      'http' => 1,
      'https' => 1,
      'ftp' => 1,
      'mailto' => 1,
      'skype' => 1,
      'hlsw' => 1,
      'steam' => 1,
      'teamspeak' => 1,
      'ts3server' => 1,
      'tmtp' => 1
    );

    if($scheme == 'javascript' AND !empty($allow_javascript) AND preg_match('=^javascript:abc_[a-z_]*\(=i', $url))
      return $url;

    if(empty($allowed[$scheme]))
      return '#';
  }

  return $url;
}

function cs_html_br($run = 1)
{
  $var = '';
  while (0 < $run)
  {
    $var .= '<br />';
    $run--;
  }
  return $var . "\n";
}

function cs_html_hr($width)
{
  return "<hr style=\"width:" . $width . "\" />\n";
}

function cs_html_img($url, $height = 0, $width = 0, $more = 0, $alt = '')
{
  global $cs_main;
  $internal = '';
  $url = cs_html_uri($url);
  if(strpos($url, '://') === false) {
    $prefix = strpos($url, $cs_main['php_self']['dirname']);
    if($prefix === false OR $prefix > 1) {
      $internal = $cs_main['php_self']['dirname'];
    }
  }
  $var = "<img src=\"" . cs_html_attr($internal . str_replace(' ', '%20', $url)) . "\" ";
  if (!empty($height) or !empty($width)) {
    $var .= 'style="';
    if (!empty($width)) {
      $var .= 'width:' . (int) $width . 'px;';
    }
    if (!empty($height)) {
      $var .= 'height:' . (int) $height . 'px;';
    }
    $var .= '" ';
  }  
  if (!empty($more))
  {
    $var .= $more . ' ';
  }
  return $var . "alt=\"" . cs_html_attr($alt) . "\" />";
}

function cs_html_link($url, $link, $use_target = 1, $class = 0, $title = 0, $more = 0)
{
  global $cs_main;
  $url = cs_html_uri(str_replace('http://http://', 'http://', $url), 1);
  if (!empty($cs_main['rss']) and strpos($url, '://') === false)
  {
    if (!empty($cs_main['php_self']['dirname']) and strpos($url, $cs_main['php_self']['dirname']) === false)
    {
      $url = $cs_main['php_self']['website'] . $cs_main['php_self']['dirname'] . $url;
    }
    else
    {
      $url = $cs_main['php_self']['website'] . $url;
    }
  }
  $var = "<a href=\"" . cs_html_attr(str_replace(' ', '%20', $url)) . "\"";
  if (!empty($use_target) and empty($cs_main['rss']))
  {
    $var .= " target=\"_blank\"";
  }
  if (!empty($class))
  {
    $var .= " class=\"" . cs_html_attr($class) . "\"";
  }
  if (!empty($title))
  {
    $var .= " title=\"" . cs_html_attr($title) . "\"";
  }
  if (!empty($more))
  {
    $var .= " " . $more;
  }
  return $var . ' >' . $link . '</a>';
}

function cs_html_anchor($name, $text = '', $more = '')
{
  return "<a href=\"#\" id=\"" . cs_html_attr($name) . "\"" . $more . " >" . $text . "</a>";
}

function cs_html_select($func, $name = '', $more = 0)
{
  if (!empty($func))
  {
    $var = "<select name=\"" . cs_html_attr($name) . "\" class=\"form\"";
    if (!empty($more))
    {
      $var .= ' ' . $more;
    }
    return $var . ">\n";
  }
  else
  {
    return "</select>\n";
  }
}

function cs_html_option($name, $value, $select = 0, $style = 0)
{
  $var = "<option value=\"" . cs_html_attr($value) . "\"";
  if (!empty($style))
  {
    $var .= " style=\"" . cs_html_attr($style) . "\"";
  }
  if (!empty($select))
  {
    $var .= " selected=\"selected\"";
  }
  return $var . '>' . cs_html_attr($name) . "</option>\n";
}

function cs_html_underline($func)
{
  $func == 1 ? $var = '<span style="text-decoration:underline>' : $var = '</span>';
  return $var;
}

function cs_html_big($func)
{
  $func == 1 ? $var = '<strong>' : $var = '</strong>';
  return $var;
}

function cs_html_italic($func)
{
  $func == 1 ? $var = '<em>' : $var = '</em>';
  return $var;
}

function cs_html_list($string, $style = 0, $element = '[*]')
{
  $var = str_replace($element, '</li><li>', $string);
  $first = strpos($var, '</li>');
  $var = substr($var, 0, $first) . substr($var, $first + 5) . '</li>';
  $var = empty($style) ? '<ul>' . $var . '</ul>' : '<ol>' . $var . '</ol>';
  return $var;
}
