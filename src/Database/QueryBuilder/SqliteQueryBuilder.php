<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Exception\PhoenixException;

class SqliteQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_TINY_INTEGER => 'tinyinteger',
        Column::TYPE_SMALL_INTEGER => 'smallinteger',
        Column::TYPE_MEDIUM_INTEGER => 'mediuminteger',
        Column::TYPE_INTEGER => 'integer',
        Column::TYPE_BIG_INTEGER => 'bigint',
        Column::TYPE_BOOLEAN => 'boolean',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATE => 'date',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_UUID => 'char(36)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_NUMERIC => 'decimal(%d,%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
        Column::TYPE_FLOAT => 'float',
        Column::TYPE_DOUBLE => 'double',
        Column::TYPE_ENUM => 'enum CHECK(%s IN (%s))',
        Column::TYPE_SET => 'enum CHECK(%s IN (%s))',
    ];

    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
        Column::TYPE_NUMERIC => [10, 0],
        Column::TYPE_DECIMAL => [10, 0],
    ];

    private $adapter;

    public function __construct(AdapterInterface $adapter = null)
    {
        $this->adapter = $adapter;
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
        $queries = $this->addColumns($table);
        if ($table->getColumnsToChange()) {
            $tmpTableName = '_' . $table->getName() . '_old_' . date('YmdHis');
            $queries = array_merge($queries, $this->renameTable($table, $tmpTableName));
            $queries = array_merge($queries, $this->createNewTable($table, $tmpTableName));

            $tableToDrop = new MigrationTable($tmpTableName);
            $queries = array_merge($queries, $this->dropTable($tableToDrop));
        }

        return $queries;
    }

    protected function createColumn(Column $column, MigrationTable $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        $col .= $column->isAutoincrement() && in_array($column->getName(), $table->getPrimaryColumns()) ? ' PRIMARY KEY AUTOINCREMENT' : '';
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        if ($column->getDefault() !== null && $column->getDefault() !== '') {
            $col .= ' DEFAULT ';
            if (in_array($column->getType(), [Column::TYPE_INTEGER, Column::TYPE_BOOLEAN])) {
                $col .= intval($column->getDefault());
            } else {
                $col .= "'" . $column->getDefault() . "'";
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        return $col;
    }

    protected function primaryKeyString(MigrationTable $table)
    {
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $column = $table->getColumn($name);
            if (!$column->isAutoincrement()) {
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
            $columns[] = $this->escapeString($table->getColumn($column)->getName());
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $query = 'CREATE ' . $indexType . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . ' (' . implode(',', $columns) . ');';
        return $query;
    }

    private function createNewTable(MigrationTable $table, $tmpTableName)
    {
        if (is_null($this->adapter)) {
            throw new PhoenixException('Missing adapter');
        }
        $oldColumns = $this->adapter->tableInfo($table->getName());
        $columns = array_merge($oldColumns, $table->getColumnsToChange());

        $newTable = new MigrationTable($table->getName());
        $columnNames = [];
        foreach ($columns as $column) {
            $columnNames[] = $column->getName();
            if ($column->isAutoincrement()) {
                $newTable->addPrimary($column);
                continue;
            }
            $newTable->addColumn($column->getName(), $column->getType(), $column->getSettings());
        }

        $queries = $this->createTable($newTable);
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
            $values = $column->getValues();
        } elseif ($column->getType() === Column::TYPE_SET) {
            $this->createSetCombinations($column->getValues(), '', $values);
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
