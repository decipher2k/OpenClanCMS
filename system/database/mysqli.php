<?php
// ClanSphere 2010 - www.clansphere.net
// $Id$

function cs_sql_connect($cs_db, $test = 0) {
  $error = '';
  if(!extension_loaded('mysqli')) {
    $error = 'PHP extension mysqli must be activated!';
  }
  else {
    mysqli_report(MYSQLI_REPORT_OFF);
    $connect = mysqli_connect($cs_db['place'], $cs_db['user'], $cs_db['pwd'], $cs_db['name']) OR $error = mysqli_connect_error();
  }

  global $cs_main;
  $sql_charset = strtolower($cs_main['charset']);
  if(empty($error) AND $sql_charset == 'utf-8') {
    mysqli_set_charset($connect, 'utf8');
  }

  if(empty($test) AND empty($error))
  return $connect;
  elseif(empty($test))
  cs_error_sql(__FILE__, 'cs_sql_connect', $error, 1);
  else
  return $error;
}

function _cs_sqli_exec_prepared($cs_file, $query, $params, $func_name) {

  global $cs_db;
  $stmt = mysqli_prepare($cs_db['con'], $query);
  if(!$stmt) {
    cs_error_sql($cs_file, $func_name, cs_sql_error(0, $query));
    return false;
  }

  if(!empty($params) AND is_array($params)) {
    $types = '';
    $bind_params = array('');
    foreach($params AS $param) {
      if(is_int($param) OR is_float($param)) {
        $types .= 'd';
      } else {
        $types .= 's';
      }
      $bind_params[] = $param;
    }
    if(!empty($types)) {
      $bind_params[0] = $types;
      $refs = array();
      foreach($bind_params AS $key => $value)
        $refs[$key] = &$bind_params[$key];
      call_user_func_array(array($stmt, 'bind_param'), $refs);
    }
  }

  if(!mysqli_stmt_execute($stmt)) {
    cs_error_sql($cs_file, $func_name, cs_sql_error(0, $query));
    mysqli_stmt_close($stmt);
    return false;
  }

  return $stmt;
}

function cs_sql_count($cs_file, $sql_table, $sql_where = 0, $distinct = 0, $params = array()) {

  global $cs_db;
  $row = empty($distinct) ? '*' : 'DISTINCT ' . $distinct;

  $sql_query = 'SELECT COUNT(' . $row . ') FROM ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_query .= empty($sql_where) ? '' : ' WHERE ' . $sql_where;
  $sql_query = str_replace('{pre}',$cs_db['prefix'],$sql_query);

  if(!empty($params) AND is_array($params)) {
    $stmt = _cs_sqli_exec_prepared($cs_file, $sql_query, $params, 'cs_sql_count');
    if($stmt) {
      mysqli_stmt_bind_result($stmt, $count);
      mysqli_stmt_fetch($stmt);
      $result = (int) $count;
      mysqli_stmt_close($stmt);
    } else {
      $result = 0;
    }
  }
  elseif(!$sql_data = mysqli_query($cs_db['con'], $sql_query)) {
    cs_error_sql($cs_file, 'cs_sql_count', cs_sql_error(0, $sql_query));
    return NULL;
  } else {
    $sql_result = mysqli_fetch_row($sql_data);
    mysqli_free_result($sql_data);
    $result = $sql_result[0];
  }
  cs_log_sql($cs_file, $sql_query);
  return $result;
}

function cs_sql_delete($cs_file, $sql_table, $sql_id, $sql_field = 0) {

  global $cs_db;
  settype($sql_id,'integer');
  if (empty($sql_field)) {
    $sql_field = $sql_table . '_id';
  }
  $sql_delete = 'DELETE FROM ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_delete .= ' WHERE ' . $sql_field . ' = ' . $sql_id;
  mysqli_query($cs_db['con'], $sql_delete) or cs_error_sql($cs_file, 'cs_sql_delete', cs_sql_error(0, $sql_delete));
  cs_log_sql($cs_file, $sql_delete,1);
}

function cs_sql_escape($string) {

  global $cs_db;
  return mysqli_real_escape_string($cs_db['con'], (string) $string);
}

function cs_sql_insert($cs_file, $sql_table, $sql_cells, $sql_content) {

  global $cs_db;
  $max = count($sql_cells);

  $placeholders = array();
  $cell_list = array();
  for($run=0; $run<$max; $run++) {
    $cell_list[] = $sql_cells[$run];
    $placeholders[] = '?';
  }

  $sql_insert = 'INSERT INTO ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_insert .= ' (' . implode(',', $cell_list) . ') VALUES (' . implode(',', $placeholders) . ')';

  _cs_sqli_exec_prepared($cs_file, $sql_insert, $sql_content, 'cs_sql_insert');
  cs_log_sql($cs_file, $sql_insert);
}

function cs_sql_insertid($cs_file) {

  global $cs_db;
  $result = mysqli_insert_id($cs_db['con']) or cs_error_sql($cs_file, 'cs_sql_insertid', cs_sql_error());
  return $result;
}

function cs_sql_option($cs_file, $mod) {

  global $cs_db, $cs_template;
  static $options = array();

  if (empty($options[$mod])) {

    if (!$options[$mod] = cs_cache_load('op_' . $mod)) {

      $sql_query = 'SELECT options_name, options_value FROM  ' . $cs_db['prefix'] . '_' . 'options';
      $sql_query .= " WHERE options_mod = ?";

      $stmt = _cs_sqli_exec_prepared($cs_file, $sql_query, array($mod), 'cs_sql_option');
      if($stmt) {
        $new_result = array();
        $result = mysqli_stmt_get_result($stmt);
        if($result) {
          while($row = mysqli_fetch_assoc($result)) {
            $new_result[$row['options_name']] = $row['options_value'];
          }
          mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
      }
      cs_log_sql($cs_file, $sql_query);
      if(count($cs_template)) {
        foreach($cs_template AS $navlist => $value) {
        if($navlist == $mod) {
          $new_result = array_merge($new_result,$value);
        }
        }
      }
      $options[$mod] = isset($new_result) ? $new_result : 0;

      cs_cache_save('op_' . $mod, $options[$mod]);
    }
  }

  return $options[$mod];
}

function cs_sql_query($cs_file, $sql_query, $more = 0, $params = array()) {

  global $cs_db;
  $sql_query = str_replace('{pre}', $cs_db['prefix'], $sql_query);

  if(!empty($params) AND is_array($params)) {
    $stmt = _cs_sqli_exec_prepared($cs_file, $sql_query, $params, 'cs_sql_query');
    if($stmt) {
      $result = array('affected_rows' => mysqli_stmt_affected_rows($stmt));
      if(!empty($more)) {
        $res = mysqli_stmt_get_result($stmt);
        if($res) {
          $result['more'] = array();
          while($row = mysqli_fetch_assoc($res)) {
            $result['more'][] = $row;
          }
          mysqli_free_result($res);
        }
      }
      mysqli_stmt_close($stmt);
    } else {
      $result = 0;
    }
  }
  elseif($sql_data = mysqli_query($cs_db['con'], $sql_query)) {
    $result = array('affected_rows' => mysqli_affected_rows($cs_db['con']));
    if(!empty($more)) {
      while ($sql_result = mysqli_fetch_assoc($sql_data)) {
        $result['more'][] = $sql_result;
      }
      mysqli_free_result($sql_data);
    }
  } else {
    cs_error_sql($cs_file, 'cs_sql_query', mysqli_error($cs_db['con']));
    $result = 0;
  }
  cs_log_sql($cs_file, $sql_query);
  return $result;
}

function cs_sql_replace($replace) {

  global $cs_db;
  $subtype = empty($cs_db['subtype']) ? 'myisam' : $cs_db['subtype'];
  $version = mysqli_get_server_info($cs_db['con']) or cs_error_sql(__FILE__, 'cs_sql_replace', cs_sql_error());
  $myv = explode('.', $version);
  settype($myv[2], 'integer');
  if($myv[0] > 4 OR $myv[0] == 4 AND $myv[1] > 1 OR $myv[0] == 4 AND $myv[1] == 1 AND $myv[2] > 7)
  $engine = ' ENGINE=' . $subtype . ' DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
  else
  $engine = ' TYPE=' . $subtype . ' CHARACTER SET utf8';

  $replace = str_replace('{optimize}','OPTIMIZE TABLE',$replace);
  $replace = str_replace('{serial}','int(8) unsigned NOT NULL auto_increment',$replace);
  $replace = str_replace('{engine}',$engine,$replace);
  return preg_replace("=create index (\S+) on (\S+) (\S+)=si",'ALTER TABLE $2 ADD KEY $1 $3',$replace);
}

function cs_sql_select($cs_file, $sql_table, $sql_select, $sql_where = 0, $sql_order = 0, $first = 0, $max = 1, $cache = 0, $params = array()) {

  if (!empty($cache) && $return = cs_cache_load($cache)) {
    return $return;
  }

  global $cs_db;
  $first = ($first < 0) ? 0 : (int) $first;
  $max = ($max < 0) ? 20 : (int) $max;
  $run = 0;

  $sql_query = 'SELECT ' . $sql_select . ' FROM ' . $cs_db['prefix'] . '_' . $sql_table;
  if (!empty($sql_where)) {
    $sql_query .= ' WHERE ' . $sql_where;
  }
  if (!empty($sql_order)) {
    $sql_query .= ' ORDER BY ' . str_replace('{random}', 'RAND()', $sql_order);
  }
  if (!empty($max)) {
    $sql_query .= ' LIMIT ' . $first . ',' . $max;
  }
  $sql_query = str_replace('{pre}', $cs_db['prefix'], $sql_query);

  if(!empty($params) AND is_array($params)) {
    $stmt = _cs_sqli_exec_prepared($cs_file, $sql_query, $params, 'cs_sql_select');
    if($stmt) {
      $res = mysqli_stmt_get_result($stmt);
      if($res) {
        $new_result = $max == 1 ? mysqli_fetch_assoc($res) : mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_free_result($res);
      }
      mysqli_stmt_close($stmt);
    }
  }
  else {
    if (!$sql_data = mysqli_query($cs_db['con'], $sql_query)) {
      cs_error_sql($cs_file, 'cs_sql_select', cs_sql_error(0, $sql_query));
      return NULL;
    }
    if ($max == 1) {
      $new_result = mysqli_fetch_assoc($sql_data);
    } else {
      while ($sql_result = mysqli_fetch_assoc($sql_data)) {
        $new_result[$run] = $sql_result;
        $run++;
      }
    }
    mysqli_free_result($sql_data);
  }

  cs_log_sql($cs_file, $sql_query);

  if (!empty($new_result)) {
    if (!empty($cache))
    cs_cache_save($cache, $new_result);

    return $new_result;
  }
  return NULL;
}

function cs_sql_update($cs_file, $sql_table, $sql_cells, $sql_content, $sql_id, $sql_where = 0, $sql_log = 1, $params = array()) {

  global $cs_db;
  settype($sql_id,'integer');
  $max = count($sql_cells);

  $set_parts = array();
  for($run=0; $run<$max; $run++) {
    $set_parts[] = $sql_cells[$run] . ' = ?';
  }
  $set = ' SET ' . implode(', ', $set_parts);

  $sql_update = 'UPDATE ' . $cs_db['prefix'] . '_' . $sql_table . $set . ' WHERE ';
  if(empty($sql_where)) {
    $sql_update .= $sql_table . '_id = ' . $sql_id;
  }
  else {
    $sql_update .= $sql_where;
  }

  $all_params = array_values($sql_content);
  if(!empty($params) AND is_array($params)) {
    $all_params = array_merge($all_params, array_values($params));
  }

  _cs_sqli_exec_prepared($cs_file, $sql_update, $all_params, 'cs_sql_update');
  cs_log_sql($cs_file, $sql_update, $sql_log);
}

function cs_sql_version($cs_file) {

  global $cs_db;
  $subtype = empty($cs_db['subtype']) ? 'myisam' : strtolower($cs_db['subtype']);
  $sql_infos = array('data_free' => 0, 'data_size' => 0, 'index_size' => 0, 'tables' => 0, 'names' => array());
  $sql_query = "SHOW TABLE STATUS LIKE '" . cs_sql_escape($cs_db['prefix'] . '_') . "%'";
  $sql_data = mysqli_query($cs_db['con'], $sql_query) or cs_error_sql($cs_file, 'cs_sql_version', cs_sql_error(0, $sql_query));
  while($row = mysqli_fetch_assoc($sql_data)) {
    $sql_infos['data_size'] += $row['Data_length'];
    $sql_infos['index_size'] += $row['Index_length'];
    $sql_infos['data_free'] += ($subtype == 'innodb') ? 0 : $row['Data_free'];
    $sql_infos['tables']++;
    $sql_infos['names'][] .= $row['Name'];
  }
  mysqli_free_result($sql_data);
  cs_log_sql($cs_file, $sql_query);

  $sql_infos['encoding'] = mysqli_character_set_name($cs_db['con']);
  $sql_infos['type'] = 'MySQL (mysqli)';
  $sql_infos['subtype'] = empty($cs_db['subtype']) ? 'myisam' : $cs_db['subtype'];
  $sql_infos['client'] = mysqli_get_client_info();
  $sql_infos['host'] = mysqli_get_host_info($cs_db['con']) or cs_error_sql($cs_file, 'cs_sql_version', cs_sql_error());
  $sql_infos['server'] = mysqli_get_server_info($cs_db['con']) or cs_error_sql($cs_file, 'cs_sql_version', cs_sql_error());
  return $sql_infos;
}

function cs_sql_error($object = 0, $query = 0) {

  global $cs_db;
  $error_string = mysqli_error($cs_db['con']);
  if(!empty($query))
    $error_string .= ' --Query: ' . $query;
  return $error_string;
}
