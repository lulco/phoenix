<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;

class SqliteQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_TINY_INTEGER => 'tinyinteger',
        Column::TYPE_SMALL_INTEGER => 'smallinteger',
        Column::TYPE_MEDIUM_INTEGER => 'mediuminteger',
        Column::TYPE_INTEGER => 'integer',
        Column::TYPE_BIG_INTEGER => 'bigint',
        Column::TYPE_NUMERIC => 'decimal(%d,%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
        Column::TYPE_FLOAT => 'float',
        Column::TYPE_DOUBLE => 'double',
        Column::TYPE_BINARY => 'binary(%d)',
        Column::TYPE_VARBINARY => 'varbinary(%d)',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_BOOLEAN => 'boolean',
        Column::TYPE_DATE => 'date',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_TINY_TEXT => 'tinytext',
        Column::TYPE_MEDIUM_TEXT => 'mediumtext',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_LONG_TEXT => 'longtext',
        Column::TYPE_TINY_BLOB => 'tinyblob',
        Column::TYPE_MEDIUM_BLOB => 'mediumblob',
        Column::TYPE_BLOB => 'blob',
        Column::TYPE_LONG_BLOB => 'longblob',
        Column::TYPE_UUID => 'char(36)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_ENUM => 'enum CHECK(%s IN (%s))',
        Column::TYPE_SET => 'enum CHECK(%s IN (%s))',
        Column::TYPE_POINT => 'point',
        Column::TYPE_LINE => 'varchar(255)',
        Column::TYPE_POLYGON => 'text',
    ];

    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
        Column::TYPE_NUMERIC => [10, 0],
        Column::TYPE_DECIMAL => [10, 0],
        Column::TYPE_BINARY => 255,
        Column::TYPE_VARBINARY => 255,
    ];

    private $structure;

    public function __construct(Structure $structure)
    {
        $this->structure = $structure;
    }

    /**
     * generates create table queries for sqlite
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function createTable(MigrationTable $table)
    {
        $queries = [];
        $queries[] = $this->createTableQuery($table);
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }
        return $queries;
    }

    /**
     * generates drop table query for sqlite
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function dropTable(MigrationTable $table)
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }

    /**
     * generates rename table queries for sqlite
     * @param MigrationTable $table
     * @param string $newTableName
     * @return array list of queries
     */
    public function renameTable(MigrationTable $table, $newTableName)
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($newTableName) . ';'];
    }

    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function alterTable(MigrationTable $table)
    {
        $queries = [];
        $tmpTableName = '_' . $table->getName() . '_old_' . date('YmdHis');
        $queries = array_merge($queries, $this->renameTable($table, $tmpTableName));
        $queries = array_merge($queries, $this->createNewTable($table, $tmpTableName));

        $tableToDrop = new MigrationTable($tmpTableName);
        $queries = array_merge($queries, $this->dropTable($tableToDrop));

        return $queries;
    }

    protected function createColumn(Column $column, MigrationTable $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        $col .= $column->getSettings()->isAutoincrement() && in_array($column->getName(), $table->getPrimaryColumns()) ? ' PRIMARY KEY AUTOINCREMENT' : '';
        $col .= $column->getSettings()->allowNull() ? '' : ' NOT NULL';
        if ($column->getSettings()->getDefault() !== null && $column->getSettings()->getDefault() !== '') {
            $col .= ' DEFAULT ';
            if (in_array($column->getType(), [Column::TYPE_INTEGER, Column::TYPE_BOOLEAN])) {
                $col .= intval($column->getSettings()->getDefault());
            } else {
                $col .= "'" . $column->getSettings()->getDefault() . "'";
            }
        } elseif ($column->getSettings()->allowNull() && $column->getSettings()->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        return $col;
    }

    protected function primaryKeyString(MigrationTable $table)
    {
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $column = $table->getColumn($name);
            if (!$column->getSettings()->isAutoincrement()) {
                $primaryKeys[] = $this->escapeString($column->getName());
            }
        }
        if (empty($primaryKeys)) {
            return '';
        }
        return 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndex(Index $index, MigrationTable $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $query = 'CREATE ' . $indexType . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . ' (' . implode(',', $columns) . ');';
        return $query;
    }

    private function dropIndex($indexName)
    {
        return 'DROP INDEX "' . $indexName . '"';
    }

    private function createNewTable(MigrationTable $table, $tmpTableName)
    {
        $queries = [];

        $oldTable = $this->structure->getTable($table->getName());
        $oldColumns = $oldTable->getColumns();
        $columnsToDrop = $table->getColumnsToDrop();
        $columnsToChange = $table->getColumnsToChange();

        $columns = array_merge($oldColumns, $columnsToChange);
        $newTable = new MigrationTable($table->getName());
        $columnNames = [];
        foreach ($columns as $column) {
            if (in_array($column->getName(), $columnsToDrop)) {
                unset($oldColumns[$column->getName()]);
                continue;
            }
            $columnNames[] = $column->getName();
            if ($column->getSettings()->isAutoincrement()) {
                $newTable->addPrimary($column);
                continue;
            }
            $newTable->addColumn($column->getName(), $column->getType(), $column->getSettings()->getSettings());
        }
        foreach ($table->getColumns() as $newColumn) {
            $newTable->addColumn($newColumn->getName(), $newColumn->getType(), $newColumn->getSettings()->getSettings());
        }

        $indexesToDrop = $table->getIndexesToDrop();
        foreach ($oldTable->getIndexes() as $index) {
            $queries[] = $this->dropIndex($index->getName());
            if (in_array($index->getName(), $indexesToDrop)) {
                continue;
            }
            $indexColumns = [];
            foreach ($index->getColumns() as $indexColumn) {
                $indexColumns[] = array_key_exists($indexColumn, $columnsToChange) ? $columnsToChange[$indexColumn]->getName() : $indexColumn;
            }
            $newTable->addIndex($indexColumns, $index->getType(), $index->getMethod(), $index->getName());
        }

        foreach ($table->getIndexes() as $index) {
            $newTable->addIndex($index->getColumns(), $index->getType(), $index->getMethod(), $index->getName());
        }

        // foreign keys

        $queries = array_merge($queries, $this->createTable($newTable));
        $queries[] = 'INSERT INTO ' . $this->escapeString($newTable->getName()) . ' (' . implode(',', $this->escapeArray($columnNames)) . ') SELECT ' . implode(',', $this->escapeArray(array_keys($oldColumns))) . ' FROM ' . $this->escapeString($tmpTableName);
        return $queries;
    }

    public function escapeString($string)
    {
        return '"' . $string . '"';
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table)
    {
        $values = [];
        if ($column->getType() == Column::TYPE_ENUM) {
            $values = $column->getSettings()->getValues();
        } elseif ($column->getType() === Column::TYPE_SET) {
            $this->createSetCombinations($column->getSettings()->getValues(), '', $values);
        }
        return sprintf($this->remapType($column), $column->getName(), implode(',', array_map(function ($value) {
            return "'$value'";
        }, $values)));
    }

    private function createSetCombinations($arr, $tmpString, &$combinations)
    {
        if ($tmpString != '') {
            $combinations[] = $tmpString;
        }

        $count = count($arr);
        for ($i = 0; $i < $count; ++$i) {
            $arrcopy = $arr;
            $elem = array_splice($arrcopy, $i, 1);
            $combination = $tmpString ? $tmpString . ',' . $elem[0] : $elem[0];
            if (sizeof($arrcopy) > 0) {
                $this->createSetCombinations($arrcopy, $combination, $combinations);
            } else {
                $combinations[] = $combination;
            }
        }
    }
}
