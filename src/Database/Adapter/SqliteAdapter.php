<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;

class SqliteAdapter extends PdoAdapter
{
    /**
     * @return SqliteQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new SqliteQueryBuilder($this);
        }
        return $this->queryBuilder;
    }

    public function tableInfo($table)
    {
        $columns = $this->execute('PRAGMA table_info(' . $this->getQueryBuilder()->escapeString($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
        $tableInfo = [];
        foreach ($columns as $column) {
            preg_match('/(.*?)\((.*?)\)/', $column['type'], $matches);
            $type = $column['type'];
            $settings = [
                'null' => !$column['notnull'],
                'default' => $column['dflt_value'],
                'autoincrement' => (bool)$column['pk'] && $column['type'] == 'integer',
            ];

            // TODO - find enum and set types and CHECK functions in them
            // only way I found is to parse result of query SELECT sql FROM sqlite_master WHERE type = 'table' AND tbl_name='$table'

            if (isset($matches[1]) && $matches[1] != '') {
                $type = $matches[1];
            }

            if ($type == 'varchar') {
                $type = Column::TYPE_STRING;
            }

            if ($type == 'bigint') {
                $type = Column::TYPE_BIG_INTEGER;
            }

            $tableInfo[$column['name']] = new Column($column['name'], $type, $settings);
        }

        return $tableInfo;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
