<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;

abstract class CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [];

    protected $defaultLength = [];

    protected function createType(Column $column, MigrationTable $table)
    {
        if (in_array($column->getType(), [Column::TYPE_NUMERIC, Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE])) {
            return sprintf(
                $this->remapType($column),
                $column->getSettings()->getLength(isset($this->defaultLength[$column->getType()][0]) ? $this->defaultLength[$column->getType()][0] : null),
                $column->getSettings()->getDecimals(isset($this->defaultLength[$column->getType()][1]) ? $this->defaultLength[$column->getType()][1] : null)
            );
        } elseif (in_array($column->getType(), [Column::TYPE_ENUM, Column::TYPE_SET])) {
            return $this->createEnumSetColumn($column, $table);
        }
        return sprintf($this->remapType($column), $column->getSettings()->getLength(isset($this->defaultLength[$column->getType()]) ? $this->defaultLength[$column->getType()] : null));
    }

    protected function remapType(Column $column)
    {
        return isset($this->typeMap[$column->getType()]) ? $this->typeMap[$column->getType()] : $column->getType();
    }

    protected function createTableQuery(MigrationTable $table)
    {
        $query = ' (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column, $table);
        }
        $query .= implode(',', $columns);
        $primaryKey = $this->createPrimaryKey($table);
        $query .= $primaryKey ? ',' . $primaryKey : '';
        $query .= $this->createForeignKeys($table);
        $query .= ');';
        return $query;
    }

    protected function addColumns(MigrationTable $table)
    {
        $columns = $table->getColumns();
        if (empty($columns)) {
            return [];
        }
        return [$this->addColumnsQuery($table, $columns) . ';'];
    }

    protected function addColumnsQuery(MigrationTable $table, array $columns)
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columnList = [];
        foreach ($columns as $column) {
            $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
        }
        $query .= implode(',', $columnList);
        return $query;
    }

    protected function createPrimaryKey(MigrationTable $table)
    {
        if (empty($table->getPrimaryColumnNames())) {
            return '';
        }
        return $this->primaryKeyString($table);
    }

    protected function addPrimaryKey(MigrationTable $table)
    {
        $primaryColumns = $table->getPrimaryColumns();
        if (empty($primaryColumns)) {
            return [];
        }

        $copyTable = new MigrationTable($table->getName());
        $newTableName = '_' . $table->getName() . '_copy_' . date('YmdHis');
        $copyTable->copy($newTableName, MigrationTable::COPY_ONLY_STRUCTURE);
        $queries = $this->copyTable($copyTable);

        $newTable = new MigrationTable($newTableName);
        $newTable->addPrimary($primaryColumns);
        $queries[] = $this->addColumnsQuery($newTable, $primaryColumns) . ',ADD ' . $this->primaryKeyString($newTable) . ';';

        // if primary key is autoincrement this would work
        $copyTable->copy($newTableName, MigrationTable::COPY_ONLY_DATA);
        $queries = array_merge($queries, $this->copyTable($copyTable));
        return $queries;
    }

    protected function dropIndexes(MigrationTable $table)
    {
        if (empty($table->getIndexesToDrop())) {
            return [];
        }
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $indexes = [];
        foreach ($table->getIndexesToDrop() as $index) {
            $indexes[] = 'DROP INDEX ' . $this->escapeString($index);
        }
        $query .= implode(',', $indexes) . ';';
        return [$query];
    }

    protected function dropColumns(MigrationTable $table)
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columns = [];
        foreach ($table->getColumnsToDrop() as $column) {
            $columns[] = 'DROP COLUMN ' . $this->escapeString($column);
        }
        $query .= implode(',', $columns) . ';';
        return $query;
    }

    protected function createForeignKeys(MigrationTable $table)
    {
        if (empty($table->getForeignKeys())) {
            return '';
        }

        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $foreignKeys[] = $this->createForeignKey($foreignKey, $table);
        }
        return ',' . implode(',', $foreignKeys);
    }

    protected function addForeignKeys(MigrationTable $table)
    {
        $queries = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }

    protected function createForeignKey(ForeignKey $foreignKey, MigrationTable $table)
    {
        $columns = [];
        foreach ($foreignKey->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $referencedColumns = [];
        foreach ($foreignKey->getReferencedColumns() as $column) {
            $referencedColumns[] = $this->escapeString($column);
        }
        $constraint = 'CONSTRAINT ' . $this->escapeString($table->getName() . '_' . $foreignKey->getName());
        $constraint .= ' FOREIGN KEY (' . implode(',', $columns) . ')';
        $constraint .= ' REFERENCES ' . $this->escapeString($foreignKey->getReferencedTable()) . ' (' . implode(',', $referencedColumns) . ')';
        if ($foreignKey->getOnDelete() != ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON DELETE ' . $foreignKey->getOnDelete();
        }
        if ($foreignKey->getOnUpdate() != ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
        }
        return $constraint;
    }

    protected function dropKeys(MigrationTable $table, $primaryKeyName, $foreignKeyPrefix)
    {
        $queries = [];
        if ($table->hasPrimaryKeyToDrop()) {
            $queries[] = $this->dropKeyQuery($table, $primaryKeyName);
        }
        foreach ($table->getForeignKeysToDrop() as $foreignKey) {
            $queries[] = $this->dropKeyQuery($table, $foreignKeyPrefix . ' ' . $this->escapeString($table->getName() . '_' . $foreignKey));
        }
        return $queries;
    }

    protected function dropKeyQuery(MigrationTable $table, $key)
    {
        return 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP ' . $key . ';';
    }

    abstract public function escapeString($string);

    protected function escapeArray(array $array)
    {
        return array_map(function ($string) {
            return $this->escapeString($string);
        }, $array);
    }

    abstract protected function createColumn(Column $column, MigrationTable $table);

    abstract protected function primaryKeyString(MigrationTable $table);

    abstract protected function createEnumSetColumn(Column $column, MigrationTable $table);
}
