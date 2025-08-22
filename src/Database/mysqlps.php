<?php
	
namespace App\Database;
use mysqli;
use mysqli_stmt;
	
class mysqlps
{
    private mysqli $connect;

    public function __construct(mysqli $connection)
    {
        $this->connect = $connection;
    }

    /* === Public methods === */

    public function insert(string $sql, array $fields, array $ondup = []): bool
    {
        $fields = $this->convertEmptyToNull($fields);
        $ondup  = $this->convertEmptyToNull($ondup);

        $types = "";
        $values = [];
        $fieldlist = "";
        $duplist = "";

        ksort($fields);
        ksort($ondup);

        foreach ($fields as $key => $value) {
            $values[] = $value;
            $types .= $this->determineType($value);
            $fieldlist .= $this->escapeIdentifier($key) . "=?,";
        }

        foreach ($ondup as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $duplist .= "`$key`=VALUES(`$key`),";
            } else {
                $values[] = $value;
                $duplist .= $this->escapeIdentifier($key) . "=?,";
                $types .= $this->determineType($value);
            }
        }

        $fieldlist = rtrim($fieldlist, ",");
        $duplist   = rtrim($duplist, ",");

        $sql = str_replace(["#fields#", "#dupes#"], [$fieldlist, $duplist], $sql);

        return $this->executeStatement($sql, $types, $values, "insert");
    }

    public function update(string $sql, array $fields = []): bool
    {
        $fields = $this->convertEmptyToNull($fields);

        $types = "";
        $values = [];
        $fieldlist = "";

        preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
        $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

        if (!empty($fields)) {
            ksort($fields);
            foreach ($fields as $key => $value) {
                $values[] = $value;
                $types .= $this->determineType($value);
                $fieldlist .= $this->escapeIdentifier($key) . "=?,";
            }
            $fieldlist = rtrim($fieldlist, ",");
            $sql = str_replace("#fields#", $fieldlist, $sql);
        } else {
            $sql = str_replace("#fields#", "", $sql);
        }

        if (!empty($wheres[1])) {
            $processed = $this->processWhereValues($wheres[1]);
            foreach ($processed as $val) {
                $values[] = $val;
                $types .= $this->determineType($val);
            }
        }

        return $this->executeStatement($sql, $types, $values, "update");
    }

    public function select(string $sql)
    {
        $types = "";
        $values = [];

        preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
        $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

        if (!empty($wheres[1])) {
            $processed = $this->processWhereValues($wheres[1]);
            foreach ($processed as $val) {
                $values[] = $val;
                $types .= $this->determineType($val);
            }
        }

        $stmt = $this->prepare($sql, "select");
        if (!$stmt) return false;

        if (!$this->bindParams($stmt, $types, $values)) {
            error_log("Bind param failed (select): " . $stmt->error);
            $stmt->close();
            return false;
        }

        if (!$stmt->execute()) {
            error_log("Execute failed (select): " . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function delete(string $sql): bool
    {
        $types = "";
        $values = [];

        preg_match_all('/\|\|(.+?)\|\|/i', $sql, $wheres);
        $sql = preg_replace('/\|\|.+?\|\|/i', '?', $sql);

        if (!empty($wheres[1])) {
            $processed = $this->processWhereValues($wheres[1]);
            foreach ($processed as $val) {
                $values[] = $val;
                $types .= $this->determineType($val);
            }
        }

        return $this->executeStatement($sql, $types, $values, "delete");
    }

    /* === Private helpers === */

    private function prepare(string $sql, string $context): ?mysqli_stmt
    {
        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed ($context): " . $this->connect->error);
            return null;
        }
        return $stmt;
    }

    private function executeStatement(string $sql, string $types, array $values, string $context): bool
    {
        $stmt = $this->prepare($sql, $context);
        if (!$stmt) return false;

        if (!$this->bindParams($stmt, $types, $values)) {
            error_log("Bind param failed ($context): " . $stmt->error);
            $stmt->close();
            return false;
        }

        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed ($context): " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }

    private function convertEmptyToNull(array $array): array
    {
        foreach ($array as $k => $v) {
            if ($v === '' || (is_string($v) && strtolower($v) === 'null')) {
                $array[$k] = null;
            }
        }
        return $array;
    }

    private function determineType($value): string
    {
        if (is_null($value)) return 's';
        if (is_numeric($value)) {
            return strpos((string)$value, '.') !== false ? 'd' : 'i';
        }
        return 's';
    }

    private function bindParams(mysqli_stmt $stmt, string $types, array &$values): bool
    {
        if ($types === '' || empty($values)) {
            return true;
        }

        $refs = [];
        foreach ($values as $k => &$v) {
            $refs[$k] = &$values[$k];
        }

        return $stmt->bind_param($types, ...$refs);
    }

    private function processWhereValues(array $wheres): array
    {
        $processed = [];
        foreach ($wheres as $value) {
            $processed[] = $value;
        }
        return $processed;
    }

    private function escapeIdentifier(string $identifier): string
    {
        if ($identifier[0] === '`' && substr($identifier, -1) === '`') {
            return $identifier;
        }

        if (strpos($identifier, '.') !== false) {
            [$table, $field] = explode('.', $identifier, 2);
            return "`" . str_replace("`", "``", $table) . "`.`" .
                   str_replace("`", "``", $field) . "`";
        }

        return "`" . str_replace("`", "``", $identifier) . "`";
    }
}
