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
     * @return array list of queries
     */
    public function renameTable(MigrationTable $table)
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($table->getNewName()) . ';'];
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
            $tableToRename = new MigrationTable($table->getName());
            $tableToRename->rename($tmpTableName);
            $queries = array_merge($queries, $this->renameTable($tableToRename));
            $queries = array_merge($queries, $this->createNewTable($table, $tmpTableName));

            $tableToDrop = new MigrationTable($tmpTableName);
            $queries = array_merge($queries, $this->dropTable($tableToDrop));
        }

        return $queries;
    }

    /**
     * {@inheritdoc}
     */
    public function copyTable(MigrationTable $table)
    {
        if ($table->getCopyType() === MigrationTable::COPY_ONLY_DATA) {
            return ['INSERT INTO ' . $this->escapeString($table->getNewName()) . ' SELECT * FROM ' . $this->escapeString($table->getName()) . ';'];
        }

        $tmpTableName = '_' . $table->getName() . '_old_' . date('YmdHis');
        $tableToRename = new MigrationTable($table->getName());
        $tableToRename->rename($tmpTableName);
        $queries = $this->renameTable($tableToRename);
        $queries = array_merge($queries, $this->createNewTable($table, $tmpTableName, $table->getCopyType() === MigrationTable::COPY_STRUCTURE_AND_DATA));

        $tableToRename = new MigrationTable($table->getName());
        $tableToRename->rename($table->getNewName());
        $queries = array_merge($queries, $this->renameTable($tableToRename));

        $tableToRename = new MigrationTable($tmpTableName);
        $tableToRename->rename($table->getName());
        $queries = array_merge($queries, $this->renameTable($tableToRename));

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

    private function createNewTable(MigrationTable $table, $newTableName, $copyData = true)
    {
        if (is_null($this->adapter)) {
            throw new PhoenixException('Missing adapter');
        }
        $oldColumns = $this->adapter->getStructure()->getTable($table->getName())->getColumns();
        $columns = array_merge($oldColumns, $table->getColumnsToChange());

        $newTable = new MigrationTable($table->getName(), false);
        $columnNames = [];
        foreach ($columns as $column) {
            $columnNames[] = $column->getName();
            if ($column->getSettings()->isAutoincrement()) {
                $newTable->addPrimary($column);
                continue;
            }
            $newTable->addColumn($column->getName(), $column->getType(), $column->getSettings()->getSettings());
        }
        $newTable->create();

        $queries = $this->createTable($newTable);
        if ($copyData) {
            $queries[] = 'INSERT INTO ' . $this->escapeString($newTable->getName()) . ' (' . implode(',', $this->escapeArray($columnNames)) . ') SELECT ' . implode(',', $this->escapeArray(array_keys($oldColumns))) . ' FROM ' . $this->escapeString($newTableName);
        }
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
