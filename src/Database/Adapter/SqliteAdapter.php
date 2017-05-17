<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
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
            $tableInfo[$column['name']] = new Column($column['name'], $type, $settings);
        }
        return $tableInfo;
    }

    protected function loadStructure()
    {
        $structure = new Structure();
        $tables = $this->execute("SELECT * FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->createMigrationTable($table['name']);
            if ($migrationTable) {
                $structure->update($migrationTable);
            }
        }
        return $structure;
    }

    private function createMigrationTable($table)
    {
        $migrationTable = new MigrationTable($table, false);
        $this->loadColumns($migrationTable, $table);
        $this->loadIndexes($migrationTable, $table);
        $this->loadForeignKeys($migrationTable, $table);
        $migrationTable->create();
        return $migrationTable;
    }

    private function loadColumns(MigrationTable $migrationTable, $table)
    {
        $columns = $this->execute('PRAGMA table_info(' . $this->getQueryBuilder()->escapeString($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
        $primaryKeys = [];
        foreach ($columns as $column) {
            if ($column['pk']) {
                $primaryKeys[$column['cid']] = $column['name'];
            }
            $this->addColumn($migrationTable, $column, $table);
        }
        ksort($primaryKeys);
        $migrationTable->addPrimary($primaryKeys);
    }

    private function addColumn(MigrationTable $migrationTable, array $column, $table)
    {
        preg_match('/(.*?)\((.*?)\)/', $column['type'], $matches);
        $type = $column['type'];
        if (isset($matches[1]) && $matches[1] != '') {
            $type = $matches[1];
        }
        list($length, $decimals) = $this->getLengthAndDecimals(isset($matches[2]) ? $matches[2] : null);
        $settings = [
            'null' => !$column['notnull'],
            'default' => strtolower($column['dflt_value']) == 'null' ? null : $column['dflt_value'],
            'autoincrement' => (bool)$column['pk'] && $type == 'integer',
            'length' => $length,
            'decimals' => $decimals,
        ];
        if ($type == 'varchar') {
            $type = Column::TYPE_STRING;
        } elseif ($type == 'bigint') {
            $type = Column::TYPE_BIG_INTEGER;
        } elseif ($type == 'char' && $length == 36) {
            $type = Column::TYPE_UUID;
            $settings['length'] = null;
        } elseif ($type == Column::TYPE_ENUM) {
            $sql = $this->execute("SELECT sql FROM sqlite_master WHERE type = 'table' AND tbl_name='$table'")->fetch(PDO::FETCH_COLUMN);
            preg_match('/CHECK\(' . $column['name'] . ' IN \((.*?)\)\)/s', $sql, $matches);
            $settings['values'] = isset($matches[1]) ? explode('\',\'', substr($matches[1], 1, -1)) : [];
        }
        $migrationTable->addColumn($column['name'], $type, $settings);
    }

    private function getLengthAndDecimals($lengthAndDecimals = null)
    {
        if ($lengthAndDecimals === null) {
            return [null, null];
        }
        $length = (int) $lengthAndDecimals;
        $decimals = null;
        if (strpos($lengthAndDecimals, ',')) {
            list($length, $decimals) = array_map('intval', explode(',', $lengthAndDecimals, 2));
        }
        return [$length, $decimals];
    }

    private function loadIndexes(MigrationTable $migrationTable, $table)
    {
        $indexList = $this->execute("PRAGMA INDEX_LIST ('$table');")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexList as $index) {
            if (substr($index['name'], 0, 6) == 'sqlite') {
                continue;
            }
            $type = $index['unique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
            $indexColumns = [];
            foreach ($this->execute("PRAGMA index_info('{$index['name']}');") as $indexColumn) {
                $indexColumns[$indexColumn['seqno']] = $indexColumn['name'];
            }
            ksort($indexColumns);
            $migrationTable->addIndex(array_values($indexColumns), $type, Index::METHOD_DEFAULT, $index['name']);
        }
    }

    private function loadForeignKeys(MigrationTable $migrationTable, $table)
    {
        $foreignKeyList = $this->execute("PRAGMA FOREIGN_KEY_LIST ('$table');")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($foreignKeyList as $foreignKeyRow) {
            $migrationTable->addForeignKey($foreignKeyRow['from'], $foreignKeyRow['table'], $foreignKeyRow['to'], $foreignKeyRow['on_delete'], $foreignKeyRow['on_update']);
        }
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
