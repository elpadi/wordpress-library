<?php

namespace WordpressLib\Plugins\Settings;

use Functional as F;

class Database
{

    protected $handle;
    protected $tables;
    protected $filepath;

    public function __construct($filepath, $tables = [])
    {
        $dirname = dirname($filepath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $this->filepath = $filepath;
        $this->handle = new() \SQLite3($this->filepath);
        $this->tables = $tables;
        foreach ($tables as $name => $schema) {
            $this->initTable($name, $schema);
        }
    }

    protected function initTable($name, $schema)
    {
        $this->tables[$name] = new Table($name, $schema, $this);
    }

    public function getHandle()
    {
        return $this->handle;
    }

    public function query($sql, $stmtProcessor = null)
    {
        foreach (
            array_filter(array_keys($this->tables), function ($name) use ($sql) {
                return strpos($sql, $name);
            }) as $name
        ) {
            $this->tables[$name]->createIfMissing();
        }
        if ($stmt = $this->handle->prepare($sql)) {
            if ($stmtProcessor) {
                $stmtProcessor($stmt);
            }
            return $stmt->execute();
        }
        throw new() \RuntimeException("Error running the query $sql");
    }

    public function select($sql, $stmtProcessor = null, $iterator = null, $col = null)
    {
        if ($result = $this->query($sql, $stmtProcessor)) {
            $values = [];
            $index = 0;
            while ($row = $result->fetchArray()) {
                $val = $col && isset($row[$col]) ? $row[$col] : $row;
                if ($iterator) {
                    call_user_func($iterator, $val, $index);
                } else {
                    $values[] = $val;
                }
                $index++;
            }
            return $values;
        }
        return null;
    }

    public static function bindColumnValue(&$stmt, $column)
    {
        $type = isset($column['type']) ? $column['type'] : (is_numeric($column['value']) ? \SQLITE3_INTEGER : \SQLITE3_TEXT);
        $stmt->bindParam(':' . $column['name'], $column['value'], $type);
    }

    public function deleteRow(string $table, $where = [])
    {
        return $this->query(sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            empty($where) ? '1' : implode(', ', F\map(F\pluck($where, 'name'), function ($column) {
                    return sprintf('%s=:%s', $column, $column);
            }))
        ), function (&$stmt) use ($where) {
            foreach ($where as $column) {
                static::bindColumnValue($stmt, $column);
            }
        });
    }

    public function updateRow(string $table, array $updates, $where = [])
    {
        return $this->query(sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', F\map(F\pluck($updates, 'name'), function ($column) {
                    return sprintf('%s=:%s', $column, $column);
            })),
            empty($where) ? '1' : implode(' AND ', F\map(F\pluck($where, 'name'), function ($column) {
                    return sprintf('%s=:%s', $column, $column);
            }))
        ), function (&$stmt) use ($updates, $where) {
            foreach ($updates as $column) {
                static::bindColumnValue($stmt, $column);
            }
            foreach ($where as $column) {
                static::bindColumnValue($stmt, $column);
            }
        });
    }

    public function insertRow(string $table, array $row)
    {
        $columns = F\pluck($row, 'name');
        return $this->query(sprintf(
            "INSERT OR REPLACE INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', F\map($columns, function ($c) {
                    return ':' . $c;
            }))
        ), function (&$stmt) use ($row) {
            foreach ($row as $column) {
                static::bindColumnValue($stmt, $column);
            }
        });
    }

    public function selectOne($sql, $col = null, $stmtProcessor = null, $iterator = null)
    {
        $values = $this->select($sql, $stmtProcessor, $iterator, $col);
        return $values && count($values) ? $values[0] : null;
    }

    public function delete()
    {
        $this->handle->close();
        unlink($this->filepath);
    }
}
