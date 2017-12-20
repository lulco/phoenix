<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;

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

    public function createTable(MigrationTable $table): array
    {
        $queries = [];
        $query = 'CREATE TABLE ' . $this->escapeString($table->getName());
        if ($table->getComment()) {
            $query .= ' /* ' . $table->getComment() . ' */';
        }
        $queries[] = $query . $this->createTableQuery($table);
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }
        return $queries;
    }

    public function dropTable(MigrationTable $table): array
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }

    public function renameTable(MigrationTable $table): array
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($table->getNewName()) . ';'];
    }

    public function alterTable(MigrationTable $table): array
    {
        $queries = $this->addColumns($table);
        if ($table->getPrimaryColumns() || $table->getColumnsToChange() || $table->getColumnsToDrop() || $table->getComment() !== null) {
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

    public function copyTable(MigrationTable $table): array
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

    protected function createColumn(Column $column, MigrationTable $table): string
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        $col .= $column->getSettings()->isAutoincrement() && in_array($column->getName(), $table->getPrimaryColumnNames()) ? ' PRIMARY KEY AUTOINCREMENT' : '';
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
        if (!empty($column->getSettings()->getComment())) {
            $col .= ' /* ' . $column->getSettings()->getComment() . ' */';
        }
        return $col;
    }

    protected function primaryKeyString(MigrationTable $table): string
    {
        $primaryKeys = [];
        foreach ($table->getPrimaryColumnNames() as $name) {
            $column = $table->getColumn($name);
            if ($column === null) {
                continue;
            }
            if (!$column->getSettings()->isAutoincrement()) {
                $primaryKeys[] = $this->escapeString($column->getName());
            }
        }
        if (empty($primaryKeys)) {
            return '';
        }
        return 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndex(Index $index, MigrationTable $table): string
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $query = 'CREATE ' . $indexType . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . ' (' . implode(',', $columns) . ');';
        return $query;
    }

    private function createNewTable(MigrationTable $table, string $newTableName, bool $copyData = true): array
    {
        $oldColumns = $this->adapter->getStructure()->getTable($table->getName())->getColumns();
        $newPrimaryColumnNames = $this->getNewPrimaryColumnNames($table);
        $columns = array_merge($table->getPrimaryColumns(), $oldColumns, $table->getColumnsToChange());

        $oldColumnNames = array_combine(array_keys($oldColumns), array_keys($oldColumns));
        $columnsToDropNames = [];
        foreach ($table->getColumnsToDrop() as $columnToDrop) {
            unset($oldColumnNames[$columnToDrop]);
            $columnsToDropNames[] = $columnToDrop;
        }

        $newTable = new MigrationTable($table->getName(), false);
        if ($table->getComment() !== null) {
            $newTable->setComment($table->getComment());
        }
        $columnNames = [];
        foreach ($columns as $column) {
            if (in_array($column->getName(), $columnsToDropNames)) {
                continue;
            }
            if (!in_array($column->getName(), $newPrimaryColumnNames)) {
                $columnNames[] = $column->getName();
            }
            if ($column->getSettings()->isAutoincrement()) {
                $newTable->addPrimary($column);
                continue;
            }
            $newTable->addColumn($column->getName(), $column->getType(), $column->getSettings()->getSettings());
        }
        $newTable->create();

        $queries = $this->createTable($newTable);
        if ($copyData) {
            $queries[] = 'INSERT INTO ' . $this->escapeString($newTable->getName()) . ' (' . implode(',', $this->escapeArray($columnNames)) . ') SELECT ' . implode(',', $this->escapeArray($oldColumnNames)) . ' FROM ' . $this->escapeString($newTableName);
        }
        return $queries;
    }

    public function escapeString(string $string): string
    {
        return '"' . $string . '"';
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table): string
    {
        $values = [];
        if ($column->getType() == Column::TYPE_ENUM) {
            $values = $column->getSettings()->getValues();
        } elseif ($column->getType() === Column::TYPE_SET) {
            $this->createSetCombinations($column->getSettings()->getValues() ?: [], '', $values);
        }
        return sprintf($this->remapType($column), $column->getName(), implode(',', array_map(function ($value) {
            return "'$value'";
        }, $values)));
    }

    private function createSetCombinations(array $arr, string $tmpString, array &$combinations): void
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

    private function getNewPrimaryColumnNames(MigrationTable $table)
    {
        $primaryColumnNames = [];
        foreach ($table->getPrimaryColumns() as $primaryColumn) {
            $primaryColumnNames[] = $primaryColumn->getName();
        }
        return $primaryColumnNames;
    }
}
