<?php

namespace WordpressLib\Plugins\Settings;

class KeyValueStore extends Database
{

    protected static $tableName = 'settings';

    public function __construct($filepath, $tables = [])
    {
        parent::__construct($filepath, [
            static::$tableName => '(key VARCHAR(255) PRIMARY KEY, value TEXT)',
        ]);
    }

    public function set($key, $val)
    {
        return $this->query(sprintf("INSERT OR REPLACE INTO %s (key,value) VALUES (:key, :val)", static::$tableName), function (&$stmt) use ($key, $val) {
            $value = serialize($val);
            $stmt->bindParam(':key', $key, \SQLITE3_TEXT);
            $stmt->bindParam(':val', $value, \SQLITE3_TEXT);
        });
    }

    public function get($key, $default = null)
    {
        $value = $this->selectOne(sprintf("SELECT value FROM %s WHERE key=:key", static::$tableName), 'value', function (&$stmt) use ($key) {
            $stmt->bindParam(':key', $key, \SQLITE3_TEXT);
        });
        return $value ? unserialize($value) : $default;
    }
}
