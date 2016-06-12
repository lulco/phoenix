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
                'autoincrement' => (bool)$column['pk'],
            ];
            if (isset($matches[1]) && $matches[1] != '') {
                $type = $matches[1];
            }
            
            if ($type == 'varchar') {
                $type = 'string';
            }
            $tableInfo[$column['name']] = new Column($column['name'], $type, $settings);
        }
        return $tableInfo;
    }
}
