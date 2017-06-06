<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
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

    protected function loadDatabase()
    {
        return 'sqlite_master';
    }

    protected function loadTables($database)
    {
        return $this->execute(sprintf("SELECT name AS table_name FROM %s WHERE type='table' AND name != 'sqlite_sequence'", $database))->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function loadColumns($database)
    {
        $tables = $this->loadTables($database);
        $tablesColumns = [];
        foreach ($tables as $table) {
            $tablesColumns[$table['table_name']]= $this->execute('PRAGMA table_info(' . $this->escapeString($table['table_name']) . ')')->fetchAll(PDO::FETCH_ASSOC);
        }
        return $tablesColumns;
    }

    protected function addColumn(MigrationTable $migrationTable, array $column)
    {
        preg_match('/(.*?)\((.*?)\)/', $column['type'], $matches);
        $type = $column['type'];
        if (isset($matches[1]) && $matches[1] != '') {
            $type = $matches[1];
        }

        list($length, $decimals) = $this->getLengthAndDecimals(isset($matches[2]) ? $matches[2] : null);

        $settings = [
            ColumnSettings::SETTING_NULL => !$column['notnull'],
            ColumnSettings::SETTING_DEFAULT => strtolower($column['dflt_value']) == 'null' ? null : $column['dflt_value'],
            ColumnSettings::SETTING_AUTOINCREMENT => (bool)$column['pk'] && $type == 'integer',
            ColumnSettings::SETTING_LENGTH => $length,
            ColumnSettings::SETTING_DECIMALS => $decimals,
        ];

        if ($type == 'varchar') {
            $type = Column::TYPE_STRING;
        } elseif ($type == 'bigint') {
            $type = Column::TYPE_BIG_INTEGER;
        } elseif ($type == 'char' && $length == 36) {
            $type = Column::TYPE_UUID;
            $settings[ColumnSettings::SETTING_LENGTH] = null;
        } elseif ($type == Column::TYPE_ENUM) {
            $sql = $this->execute(sprintf("SELECT sql FROM sqlite_master WHERE type = 'table' AND tbl_name='%s'", $migrationTable->getName()))->fetch(PDO::FETCH_COLUMN);
            preg_match('/CHECK\(' . $column['name'] . ' IN \((.*?)\)\)/s', $sql, $matches);
            $settings[ColumnSettings::SETTING_VALUES] = isset($matches[1]) ? explode('\',\'', substr($matches[1], 1, -1)) : [];
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

    protected function loadIndexes($database)
    {
        $tables = $this->loadTables($database);
        $tablesIndexes = [];
        foreach ($tables as $table) {
            $primaryColumns = $this->loadPrimaryColumns($table['table_name']);
            if (!empty($primaryColumns)) {
                $tablesIndexes[$table['table_name']]['PRIMARY']['columns'] = $primaryColumns;
            }
            $indexList = $this->execute(sprintf("PRAGMA INDEX_LIST ('%s');", $table['table_name']))->fetchAll(PDO::FETCH_ASSOC);
            foreach ($indexList as $index) {
                if (substr($index['name'], 0, 6) == 'sqlite') {
                    continue;
                }
                $indexColumns = [];
                $indexes = $this->execute(sprintf("PRAGMA index_info('%s');", $index['name']));
                foreach ($indexes as $indexColumn) {
                    $indexColumns[$indexColumn['seqno']] = $indexColumn['name'];
                }
                $tablesIndexes[$table['table_name']][$index['name']]['columns'] = $indexColumns;
                $tablesIndexes[$table['table_name']][$index['name']]['type'] = $index['unique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
                $tablesIndexes[$table['table_name']][$index['name']]['method'] = Index::METHOD_DEFAULT;
            }
        }
        return $tablesIndexes;
    }

    private function loadPrimaryColumns($tableName)
    {
        $columns = $this->execute(sprintf("PRAGMA table_info('%s')", $tableName))->fetchAll(PDO::FETCH_ASSOC);
        $primaryColumns = [];
        foreach ($columns as $column) {
            if ($column['pk']) {
                $primaryColumns[$column['cid']] = $column['name'];
            }
        }
        return $primaryColumns;
    }

    protected function loadForeignKeys($database)
    {
        $tables = $this->loadTables($database);
        $foreignKeys = [];
        foreach ($tables as $table) {
            $foreignKeyList = $this->execute(sprintf("PRAGMA FOREIGN_KEY_LIST ('%s');", $table['table_name']))->fetchAll(PDO::FETCH_ASSOC);
            foreach ($foreignKeyList as $foreignKeyRow) {
                $foreignKeys[$table['table_name']][$foreignKeyRow['from']]['columns'][] = $foreignKeyRow['from'];
                $foreignKeys[$table['table_name']][$foreignKeyRow['from']]['referenced_table'] = $foreignKeyRow['table'];
                $foreignKeys[$table['table_name']][$foreignKeyRow['from']]['referenced_columns'][] = $foreignKeyRow['to'];
                $foreignKeys[$table['table_name']][$foreignKeyRow['from']]['on_delete'] = $foreignKeyRow['on_delete'];
                $foreignKeys[$table['table_name']][$foreignKeyRow['from']]['on_update'] = $foreignKeyRow['on_update'];
            }
        }
        return $foreignKeys;
    }

    protected function escapeString($string)
    {
        return '"' . $string . '"';
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
