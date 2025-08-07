<?php

/**
 * Prepared INSERT with optional ON DUPLICATE KEY UPDATE.
 * SQL must include #fields# and optionally #dupes#.
 */
function mysqli_ps_insert($connect, $sql, $fields, $ondup = null) {
    $types = '';
    $values = [];
    $fieldlist = '';
    $duplist = '';

    if (is_array($fields)) ksort($fields);
    if (is_array($ondup)) ksort($ondup);

    foreach ($fields as $key => $value) {
        $values[] = $value;
        $types .= mysqli_determine_type($value);
        $fieldlist .= "`$key`=?,";
    }

    if (is_array($ondup)) {
        foreach ($ondup as $key => $value) {
            if (isset($fields[$key])) {
                $duplist .= "`$key`=VALUES(`$key`),";
            } else {
                $values[] = $value;
                $types .= mysqli_determine_type($value);
                $duplist .= "`$key`=?,";
            }
        }
    }

    $sql = str_replace("#fields#", rtrim($fieldlist, ","), $sql);
    $sql = str_replace("#dupes#", rtrim($duplist, ","), $sql);

    try {
        $stmt = mysqli_prepare($connect, $sql);
        if (!$stmt) throw new Exception(mysqli_error($connect));

        if (!empty($values)) {
            mysqli_bind_params($stmt, $types, $values);
        }

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    } catch (Exception $e) {
        error_log($e);
        return false;
    }
}

/**
 * Prepared UPDATE statement.
 * SQL to include #fields# (optional) and ||value|| placeholders for WHERE conditions.
 */
function mysqli_ps_update($connect, $sql, $fields = []) {
    $types = '';
    $values = [];
    $fieldlist = '';

    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($fields)) {
        ksort($fields);
        foreach ($fields as $key => $value) {
            $values[] = $value;
            $types .= mysqli_determine_type($value);
            $fieldlist .= "`$key`=?,";
        }
        $sql = str_replace("#fields#", rtrim($fieldlist, ","), $sql);
    } else {
        $sql = str_replace("#fields#", '', $sql);
    }

    foreach ($wheres[1] as $value) {
        $values[] = $value;
        $types .= mysqli_determine_type($value);
    }

    try {
        $stmt = mysqli_prepare($connect, $sql);
        if (!$stmt) throw new Exception(mysqli_error($connect));

        if (!empty($values)) {
            mysqli_bind_params($stmt, $types, $values);
        }

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    } catch (Exception $e) {
        error_log($e);
        return false;
    }
}

/**
 * Prepared SELECT with ||value|| placeholders.
 */
function mysqli_ps_select($connect, $sql) {
    $types = '';
    $values = [];

    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    foreach ($wheres[1] as $value) {
        $values[] = $value;
        $types .= mysqli_determine_type($value);
    }

    try {
        $stmt = mysqli_prepare($connect, $sql);
        if (!$stmt) throw new Exception(mysqli_error($connect));

        if (!empty($values)) {
            mysqli_bind_params($stmt, $types, $values);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    } catch (Exception $e) {
        error_log($e);
        return false;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                          🔧 Internal Helper Functions                      //
///////////////////////////////////////////////////////////////////////////////

/**
 * Determines the appropriate bind_param type for a given value.
 */
function mysqli_determine_type($value) {
    if (is_null($value)) return 's'; // Can adjust if you want NULL handled differently
    if (is_int($value)) return 'i';
    if (is_float($value)) return 'd';

    if (is_numeric($value)) {
        return (strpos((string)$value, '.') !== false) ? 'd' : 'i';
    }

    return 's';
}

/**
 * Binds parameters to a MySQLi prepared statement.
 */
function mysqli_bind_params($stmt, $types, $values) {
    $params = array_merge([$types], $values);
    $refs = [];

    foreach ($params as $k => &$v) {
        $refs[$k] = &$v;
    }

    call_user_func_array([$stmt, 'bind_param'], $refs);
}

?>
