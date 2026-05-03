<?php
// OpenClanCMS 2010 - www.clansphere.net
// $Id$

function cs_sql_connect($cs_db, $test = 0) {

  $error = '';
  if(!extension_loaded('sqlite3')) {
    $error = 'PHP extension sqlite3 must be activated!';
  }
  else {
    try {
      $connect = new sqlite3($cs_db['name']);
    }
    catch(Exception $err) {
      $error = $err->getMessage();
    }
  }

  if(empty($test) AND empty($error)) {
    return $connect;
  }
  elseif(empty($test)) {
    cs_error_sql(__FILE__, 'cs_sql_connect', $error, 1);
  }
  else {
    return $error;
  }
}

function _cs_sqlite3_prepare_exec($query, $params = array()) {
  global $cs_db;
  $stmt = $cs_db['con']->prepare($query);
  if(!$stmt)
    return false;
  $idx = 1;
  foreach(array_values($params) AS $param) {
    $type = is_int($param) ? SQLITE3_INTEGER : (is_float($param) ? SQLITE3_FLOAT : SQLITE3_TEXT);
    $stmt->bindValue($idx++, $param, $type);
  }
  return $stmt->execute();
}

function cs_sql_count($cs_file, $sql_table, $sql_where = 0, $distinct = 0, $params = array()) {

  global $cs_db;
  $row = empty($distinct) ? '*' : 'DISTINCT ' . $distinct;

  $sql_query = 'SELECT COUNT('.$row.') FROM ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_query .= empty($sql_where) ? '' : ' WHERE ' . $sql_where;
  $sql_query = str_replace('{pre}',$cs_db['prefix'],$sql_query);

  if(!empty($params) AND is_array($params)) {
    $sql_data = _cs_sqlite3_prepare_exec($sql_query, $params);
    if($sql_data) {
      $sql_result = $sql_data->fetcharray(SQLITE3_NUM);
      $result = $sql_result[0];
    } else {
      cs_error_sql($cs_file, 'cs_sql_count', cs_sql_error(0, $sql_query));
      return NULL;
    }
  }
  else {
    if(!$sql_data = $cs_db['con']->query($sql_query)) {
      cs_error_sql($cs_file, 'cs_sql_count', cs_sql_error(0, $sql_query));
      return NULL;
    }
    $sql_result = $sql_data->fetcharray(SQLITE3_NUM);
    $result = $sql_result[0];
  }
  cs_log_sql($cs_file, $sql_query);
  return $result;
}

function cs_sql_delete($cs_file, $sql_table, $sql_id, $sql_field = 0) {

  global $cs_db;
  settype($sql_id,'integer');
  if(empty($sql_field)) {
    $sql_field = $sql_table . '_id';
  }
  $sql_delete = 'DELETE FROM ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_delete .= ' WHERE ' . $sql_field . ' = ' . $sql_id;
  $cs_db['con']->query($sql_delete) OR
  cs_error_sql($cs_file, 'cs_sql_delete', cs_sql_error(0, $sql_delete));
  cs_log_sql($cs_file, $sql_delete,1);
}

function cs_sql_escape($string) {

  global $cs_db;
  return $cs_db['con']->escapestring((string) $string);
}

function cs_sql_insert($cs_file, $sql_table, $sql_cells, $sql_content) {

  global $cs_db;
  $max = count($sql_cells);
  $placeholders = array();
  $cell_list = array();
  for($run=0; $run<$max; $run++) {
    $cell_list[] = $sql_cells[$run];
    $placeholders[] = ':p' . ($run + 1);
  }

  $sql_insert = 'INSERT INTO ' . $cs_db['prefix'] . '_' . $sql_table;
  $sql_insert .= ' (' . implode(',', $cell_list) . ') VALUES (' . implode(',', $placeholders) . ')';

  $stmt = $cs_db['con']->prepare($sql_insert);
  if($stmt) {
    $idx = 1;
    foreach($sql_content AS $val) {
      $type = is_int($val) ? SQLITE3_INTEGER : (is_float($val) ? SQLITE3_FLOAT : SQLITE3_TEXT);
      $stmt->bindValue(':p' . $idx++, $val, $type);
    }
    $stmt->execute() OR cs_error_sql($cs_file, 'cs_sql_insert', cs_sql_error(0, $sql_insert));
  } else {
    cs_error_sql($cs_file, 'cs_sql_insert', cs_sql_error(0, $sql_insert));
  }
  cs_log_sql($cs_file, $sql_insert);
}

function cs_sql_insertid($cs_file) {

  global $cs_db;
  $result = $cs_db['con']->lastinsertrowid() OR
  cs_error_sql($cs_file, 'cs_sql_insertid', cs_sql_error());
  return $result;
}

function cs_sql_option($cs_file, $mod) {

  global $cs_db, $cs_template;
  static $options = array();

  if (empty($options[$mod])) {

    if (!$options[$mod] = cs_cache_load('op_' . $mod)) {

      $sql_query = 'SELECT options_name, options_value FROM  ' . $cs_db['prefix'] . '_' . 'options';
      $sql_query .= " WHERE options_mod = :mod";

      $stmt = $cs_db['con']->prepare($sql_query);
      if($stmt) {
        $stmt->bindValue(':mod', $mod, SQLITE3_TEXT);
        $sql_data = $stmt->execute();
        if($sql_data) {
          while($sql_result = $sql_data->fetcharray(SQLITE3_ASSOC)) {
            $name = $sql_result['options_name'];
            $new_result[$name] = $sql_result['options_value'];
          }
        }
      } else {
        $sql_data = $cs_db['con']->query($sql_query) OR
        cs_error_sql($cs_file, 'cs_sql_option', cs_sql_error(0, $sql_query), 1);
      }
      cs_log_sql($cs_file, $sql_query);
      if(!empty($cs_template)) {
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
  $sql_query = str_replace('{pre}',$cs_db['prefix'],$sql_query);

  if(!empty($params) AND is_array($params)) {
    $sql_data = _cs_sqlite3_prepare_exec($sql_query, $params);
    if($sql_data) {
      $result = array('affected_rows' => $cs_db['con']->changes());
      if(!empty($more)) {
        while($sql_result = $sql_data->fetcharray(SQLITE3_ASSOC)) {
          $result['more'][] = $sql_result;
        }
      }
    } else {
      cs_error_sql($cs_file, 'cs_sql_query', cs_sql_error(0, $sql_query));
      $result = 0;
    }
  }
  else {
    if($sql_data = $cs_db['con']->query($sql_query)) {
      $result = array('affected_rows' => $cs_db['con']->changes());
      if(!empty($more)) {
        while($sql_result = $sql_data->fetcharray(SQLITE3_ASSOC)) {
          $result['more'][] = $sql_result;
        }
      }
    }
    else {
      cs_error_sql($cs_file, 'cs_sql_query', cs_sql_error(0, $sql_query));
      $result = 0;
    }
  }
  cs_log_sql($cs_file, $sql_query);
  return $result;
}

function cs_sql_replace($replace) {
  $replace = str_replace('{optimize}','VACUUM',$replace);
  $replace = str_replace('{serial}','integer',$replace);
  $replace = str_replace('{engine}','',$replace);
  return preg_replace("=int\((.*?)\)=si",'integer',$replace);
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
  if(!empty($sql_where)) {
    $sql_query .= ' WHERE ' . $sql_where;
  }
  if(!empty($sql_order)) {
    $sql_query .= ' ORDER BY ' . str_replace('{random}', 'RANDOM()', $sql_order);
  }
  if(!empty($max)) {
    $sql_query .= ' LIMIT ' . $first . ',' . $max;
  }
  $sql_query = str_replace('{pre}',$cs_db['prefix'],$sql_query);

  if(!empty($params) AND is_array($params)) {
    $sql_data = _cs_sqlite3_prepare_exec($sql_query, $params);
    if($sql_data) {
      $new_result = $max == 1 ? $sql_data->fetcharray(SQLITE3_ASSOC) : array();
      if($max > 1) {
        $new_result = array();
        while($sql_result = $sql_data->fetcharray(SQLITE3_ASSOC)) {
          $new_result[$run] = $sql_result;
          $run++;
        }
      }
    } else {
      cs_error_sql($cs_file, 'cs_sql_select', cs_sql_error(0, $sql_query));
    }
  }
  else {
    if (!$sql_data = $cs_db['con']->query($sql_query)) {
      cs_error_sql($cs_file, 'cs_sql_select', cs_sql_error(0, $sql_query));
      return NULL;
    }
    if($max == 1) {
      $new_result = $sql_data->fetcharray(SQLITE3_ASSOC);
    }
    else {
      while($sql_result = $sql_data->fetcharray(SQLITE3_ASSOC)) {
        $new_result[$run] = $sql_result;
        $run++;
      }
    }
  }
  cs_log_sql($cs_file, $sql_query);

  if(!empty($new_result)) {
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
    $set_parts[] = $sql_cells[$run] . ' = :s' . ($run + 1);
  }
  $set = ' SET ' . implode(', ', $set_parts);

  $sql_update = 'UPDATE ' . $cs_db['prefix'] . '_' . $sql_table . $set . ' WHERE ';
  if(empty($sql_where)) {
    $sql_update .= $sql_table . '_id = ' . $sql_id;
  } else {
    $sql_update .= $sql_where;
  }

  $all_params = array_values($sql_content);
  if(!empty($params) AND is_array($params)) {
    $all_params = array_merge($all_params, array_values($params));
  }

  $stmt = $cs_db['con']->prepare($sql_update);
  if($stmt) {
    $idx = 1;
    foreach($all_params AS $val) {
      $type = is_int($val) ? SQLITE3_INTEGER : (is_float($val) ? SQLITE3_FLOAT : SQLITE3_TEXT);
      $stmt->bindValue(':s' . $idx++, $val, $type);
    }
    $stmt->execute() OR cs_error_sql($cs_file, 'cs_sql_update', cs_sql_error(0, $sql_update));
  } else {
    cs_error_sql($cs_file, 'cs_sql_update', cs_sql_error(0, $sql_update));
  }
  cs_log_sql($cs_file, $sql_update, $sql_log);
}

function cs_sql_version($cs_file) {

  global $cs_db;
  $version = $cs_db['con']->version();
  $sql_infos = array('data_free' => 0, 'data_size' => 0, 'index_size' => 0, 'tables' => 0, 'names' => array());
  $sql_infos['type'] = 'SQLite 3 (sqlite3)';
  $sql_infos['host'] = 'localhost';
  $sql_infos['client'] = $version['versionString'];
  $sql_infos['server'] = $version['versionString'];
  $sql_infos['encoding'] = 'default';

  $sql_query = 'SELECT COUNT(*) FROM sqlite_master WHERE type = \'table\' AND name LIKE \'' . $cs_db['prefix'] . '_%\'';
  $sql_query = str_replace('{pre}',$cs_db['prefix'],$sql_query);
  if(!$sql_data = $cs_db['con']->query($sql_query)) {
    cs_error_sql($cs_file, 'cs_sql_count', cs_sql_error(0, $sql_query));
  }
  else {
    $sql_result = $sql_data->fetcharray(SQLITE3_NUM);
    $sql_infos['tables'] = $sql_result[0];
    $sql_infos['data_size'] = filesize($cs_db['name']);
  }
  cs_log_sql($cs_file, $sql_query);
  return $sql_infos;
}

function cs_sql_error($object = 0, $query = 0) {

  global $cs_db;
  $cs_db['con'] = isset($cs_db['con']) ? $cs_db['con'] : $object;
  $error_code = $cs_db['con']->lasterrorcode();
  $error_string = empty($error_code) ? '' : $error_code . ' - ' . $cs_db['con']->lasterrormsg();
  if(!empty($query))
    $error_string .= ' --Query: ' . $query;
  return empty($error_string) ? FALSE : $error_string;
}
