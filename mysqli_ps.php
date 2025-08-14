<?
function mysqli_ps_insert($connect, $sql, $fields, $ondup = null)
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
        $fieldlist .= "`" . $key . "`=?,";
    }

    foreach ($ondup as $key => $value) {
        if (array_key_exists($key, $fields)) {
            // Use VALUES(col) if key is in fields (avoid duplicating value param)
            $duplist .= "`" . $key . "`=VALUES(`" . $key . "`),";
        } else {
            // If key not in fields, add param to values and type
            $values[] = $value;
            $duplist .= "`" . $key . "`=?,";
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

function mysqli_ps_update($connect, $sql, $fields)
{
    $fields = mysqli_convert_empty_to_null($fields);

    $types = "";
    $values = [];
    $fieldlist = "";

    // Extract WHERE values inside ||...|| and replace with ?
    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($fields)) {
        ksort($fields);
        foreach ($fields as $key => $value) {
            $values[] = $value;
            $types .= mysqli_determine_type($value);
            $fieldlist .= "`" . $key . "`=?,";
        }
        $fieldlist = rtrim($fieldlist, ",");
        $sql = str_replace("#fields#", $fieldlist, $sql);
    } else {
        // No fields to update — just remove #fields# placeholder safely
        $sql = str_replace("#fields#", "", $sql);
    }

    // Add WHERE clause values to values/types
    if (!empty($wheres[1])) {
        foreach ($wheres[1] as $value) {
            $values[] = $value;
            $types .= mysqli_determine_type($value);
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

    // Extract WHERE values inside ||...|| and replace with ?
    preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
    $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

    if (!empty($wheres[1])) {
        foreach ($wheres[1] as $value) {
            $values[] = $value;
            $types .= mysqli_determine_type($value);
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
	
	// Extract WHERE values inside ||...|| and replace with ?
	preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
	$sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);
		
	if (!empty($wheres[1])) {
		foreach ($wheres[1] as $value) {
			$values[] = $value;
			$types .= mysqli_determine_type($value);
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
		
	$success = mysqli_stmt_get_result($stmt);
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
        return 's'; // Bind NULL as string type (MySQLi accepts NULLs bound as 's')
    }

    // Accept numeric strings as numbers
    if (is_numeric($value)) {
        if (strpos($value, '.') !== false) {
            return 'd'; // float
        }
        return 'i'; // int
    }

    return 's'; // default string
}

function mysqli_bind_params(mysqli_stmt $stmt, string $types, array &$values): bool
{
    if ($types === '' || empty($values)) {
        // No params to bind
        return true;
    }

    $refs = [];
    foreach ($values as $key => &$value) {
        if (is_null($value)) {
            $nullVar = null;
            $refs[$key] = &$nullVar;
        } else {
            $refs[$key] = &$value;
        }
    }

    // Bind params with splat operator for references
    return mysqli_stmt_bind_param($stmt, $types, ...$refs);
}
?>
