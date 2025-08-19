<?php 
function mysqli_ps_insert($connect, $sql, $fields, $ondup = [])
{
    $fields = mysqli_convert_empty_to_null($fields);
    $ondup = mysqli_convert_empty_to_null(is_array($ondup) ? $ondup : []);

    $types = "";
    $values = [];
    $fieldlist = "";
    $duplist = "";

    if (is_array($fields)) {
        ksort($fields);
    }
    if (is_array($ondup)) {
        ksort($ondup);
    }

    foreach ($fields as $key => $value) {
        $values[] = $value;
        $types .= mysqli_determine_type($value);
        $fieldlist .= "`$key`=?,";
    }

    foreach ($ondup as $key => $value) {
        if (array_key_exists($key, $fields)) {
            $duplist .= "`$key`=VALUES(`$key`),";
        } else {
            $values[] = $value;
            $duplist .= "`$key`=?,";
            $types .= mysqli_determine_type($value);
        }
    }

    $fieldlist = rtrim($fieldlist, ",");
    $duplist = rtrim($duplist, ",");

    $sql = str_replace("#fields#", $fieldlist, $sql);
    $sql = str_replace("#dupes#", $duplist, $sql);

    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) {
        error_log("Prepare failed (insert): " . mysqli_error($connect));
        return false;
    }

    if (!mysqli_bind_params($stmt, $types, $values)) {
        error_log("Bind param failed (insert): " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

function mysqli_ps_update($connect, $sql, $fields=[])
{
    $fields = mysqli_convert_empty_to_null($fields);

    $types = "";
    $values = [];
    $fieldlist = "";

    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($fields)) {
        ksort($fields);
        foreach ($fields as $key => $value) {
            $values[] = $value;
            $types .= mysqli_determine_type($value);
            $fieldlist .= "`$key`=?,";
        }
        $fieldlist = rtrim($fieldlist, ",");
        $sql = str_replace("#fields#", $fieldlist, $sql);
    } else {
        $sql = str_replace("#fields#", "", $sql);
    }

    if (!empty($wheres[1])) {
        $processed = mysqli_process_where_values($wheres[1]);
        foreach ($processed as $val) {
            $values[] = $val;
            $types .= mysqli_determine_type($val);
        }
    }

    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) {
        error_log("Prepare failed (update): " . mysqli_error($connect));
        return false;
    }

    if (!mysqli_bind_params($stmt, $types, $values)) {
        error_log("Bind param failed (update): " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

function mysqli_ps_select($connect, $sql)
{
    $types = "";
    $values = [];

    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($wheres[1])) {
        $processed = mysqli_process_where_values($wheres[1]);
        foreach ($processed as $val) {
            $values[] = $val;
            $types .= mysqli_determine_type($val);
        }
    }
    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) {
        error_log("Prepare failed (select): " . mysqli_error($connect));
        return false;
    }

    if (!mysqli_bind_params($stmt, $types, $values)) {
        error_log("Bind param failed (select): " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed (select): " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function mysqli_ps_delete($connect, $sql)
{
    $types = "";
    $values = [];

    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($wheres[1])) {
        $processed = mysqli_process_where_values($wheres[1]);
        foreach ($processed as $val) {
            $values[] = $val;
            $types .= mysqli_determine_type($val);
        }
    }

    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) {
        error_log("Prepare failed (delete): " . mysqli_error($connect));
        return false;
    }

    if (!mysqli_bind_params($stmt, $types, $values)) {
        error_log("Bind param failed (delete): " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

/* === Helper functions === */

function mysqli_convert_empty_to_null(array $array): array
{
    foreach ($array as $k => $v) {
        if ($v === '' || (is_string($v) && strtolower($v) === 'null')) {
            $array[$k] = null;
        }
    }
    return $array;
}

function mysqli_determine_type($value): string
{
    if (is_null($value)) {
        return 's';
    }

    if (is_numeric($value)) {
        return strpos((string)$value, '.') !== false ? 'd' : 'i';
    }

    return 's';
}

function mysqli_bind_params(mysqli_stmt $stmt, string $types, array &$values): bool
{
    if ($types === '' || empty($values)) {
        return true;
    }

    $refs = [];
    foreach ($values as $k => &$v) {
        $refs[$k] = &$values[$k];
    }

    return call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
}

function mysqli_process_where_values(array $wheres): array
{
    $processed = [];
    foreach ($wheres as $value) {
        // If user includes % in the placeholder, keep it
        $processed[] = $value;
    }
    return $processed;
}
?>
