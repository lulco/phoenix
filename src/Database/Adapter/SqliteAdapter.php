<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;

class SqliteAdapter extends PdoAdapter
{
    /**
     * @return SqliteQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new SqliteQueryBuilder($this->getStructure());
        }
        return $this->queryBuilder;
    }

    protected function loadStructure()
    {
        $structure = new Structure();
        $tables = $this->execute("SELECT * FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->tableInfo($table['name']);
            if ($migrationTable) {
                $structure->update($migrationTable);
            }
        }
        return $structure;
    }

    private function tableInfo($table)
    {
        $columns = $this->execute('PRAGMA table_info(' . $this->escapeString($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
        $migrationTable = new MigrationTable($table);
        foreach ($columns as $column) {
            preg_match('/(.*?)\((.*?)\)/', $column['type'], $matches);
            $type = $column['type'];
            $settings = [
                'null' => !$column['notnull'],
                'default' => strtolower($column['dflt_value']) == 'null' ? null : $column['dflt_value'],
                'autoincrement' => (bool)$column['pk'] && $column['type'] == 'integer',
            ];

            if (isset($matches[1]) && $matches[1] != '') {
                $type = $matches[1];
            }

            if ($type == 'varchar') {
                $type = Column::TYPE_STRING;
            } elseif ($type == 'bigint') {
                $type = Column::TYPE_BIG_INTEGER;
            } elseif ($type == 'enum') {
                $sql = $this->execute("SELECT sql FROM sqlite_master WHERE type = 'table' AND tbl_name='$table'")->fetch(PDO::FETCH_COLUMN);
                preg_match('/CHECK\(' . $column['name'] . ' IN \((.*?)\)\)/s', $sql, $matches);
                $settings['values'] = isset($matches[1]) ? explode('\',\'', substr($matches[1], 1, -1)) : [];
            }
            $migrationTable->addColumn($column['name'], $type, $settings);
        }
        return $migrationTable;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function escapeString($string)
    {
        return '"' . $string . '"';
    }
}
