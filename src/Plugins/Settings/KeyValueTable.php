<?php

namespace WordpressLib\Plugins\Settings;

class KeyValueTable extends Table
{

    public function getTableSchema()
    {
        return '(key VARCHAR(255) PRIMARY KEY, value TEXT)';
    }
}
