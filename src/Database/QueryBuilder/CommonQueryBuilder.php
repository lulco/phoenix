<?php

namespace Phoenix\Database\QueryBuilder;

use Exception;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Table;

abstract class CommonQueryBuilder
{
    protected $typeMap = [];
    
    protected $defaultLength = [];
    
    protected function createType(Column $column)
    {
        if ($column->getType() == Column::TYPE_DECIMAL) {
            return sprintf(
                $this->remapType($column),
                $column->getLength(isset($this->defaultLength[$column->getType()][0]) ? $this->defaultLength[$column->getType()][0] : null),
                $column->getDecimals(isset($this->defaultLength[$column->getType()][1]) ? $this->defaultLength[$column->getType()][1] : null)
            );
        }
        return sprintf($this->remapType($column), $column->getLength(isset($this->defaultLength[$column->getType()]) ? $this->defaultLength[$column->getType()] : null));
    }
    
    protected function remapType(Column $column)
    {
        if (!isset($this->typeMap[$column->getType()])) {
            throw new Exception('Type "' . $column->getType() . '" is not allowed');
        }
        return $this->typeMap[$column->getType()];
    }
    
    protected function createTableQuery(Table $table)
    {
        $query = 'CREATE TABLE ' . $this->escapeString($table->getName()) . ' (';
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
    
    protected function addColumns(Table $table)
    {
        $columns = $table->getColumns();
        if (empty($columns)) {
            return [];
        }
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columnList = [];
        foreach ($columns as $column) {
            $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
        }
        $query .= implode(',', $columnList) . ';';
        return [$query];
    }
    
    protected function createPrimaryKey(Table $table)
    {
        if (empty($table->getPrimaryColumns())) {
            return '';
        }
        return $this->primaryKeyString($table);
    }
    
    protected function addPrimaryKey(Table $table)
    {
        $queries = [];
        $primaryColumns = $table->getPrimaryColumns();
        if (!empty($primaryColumns)) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->primaryKeyString($table) . ';';
        }
        return $queries;
    }
    
    protected function dropIndexes(Table $table)
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
    
    protected function dropColumns(Table $table)
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columns = [];
        foreach ($table->getColumnsToDrop() as $column) {
            $columns[] = 'DROP COLUMN ' . $this->escapeString($column);
        }
        $query .= implode(',', $columns) . ';';
        return $query;
    }
    
    protected function createForeignKeys(Table $table)
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
    
    protected function addForeignKeys(Table $table)
    {
        $queries = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }
    
    protected function createForeignKey(ForeignKey $foreignKey, Table $table)
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

    abstract public function escapeString($string);
    
    protected function escapeArray(array $array)
    {
        return array_map(function ($string) {
            return $this->escapeString($string);
        }, $array);
    }
    
    abstract protected function createColumn(Column $column, Table $table);
    
    abstract protected function primaryKeyString(Table $table);
}
