<?php

namespace Phoenix\Database\QueryBuilder;

use Exception;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Table;

trait CommonQueryBuilder
{
    private function createType(Column $column)
    {
        return sprintf($this->remapType($column), $column->getLength(isset($this->defaultLength[$column->getType()]) ? $this->defaultLength[$column->getType()] : null));
    }
    
    protected function remapType(Column $column)
    {
        if (!isset($this->typeMap[$column->getType()])) {
            throw new Exception('Type "' . $column->getType() . '" is not allowed');
        }
        return $this->typeMap[$column->getType()];
    }
    
    private function createTableQuery(Table $table)
    {
        $query = 'CREATE TABLE ' . $this->escapeString($table->getName()) . ' (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column, $table);
        }
        $query .= implode(',', $columns);
        $query .= $this->createPrimaryKey($table);
        $query .= $this->createForeignKeys($table);
        $query .= ');';
        return $query;
    }
    
    private function dropIndexes(Table $table)
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $indexes = [];
        foreach ($table->getIndexesToDrop() as $index) {
            $indexes[] = 'DROP INDEX ' . $this->escapeString($index);
        }
        $query .= implode(',', $indexes) . ';';
        return $query;
    }
    
    private function dropColumns(Table $table)
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columns = [];
        foreach ($table->getColumnsToDrop() as $column) {
            $columns[] = 'DROP COLUMN ' . $this->escapeString($column);
        }
        $query .= implode(',', $columns) . ';';
        return $query;
    }
    
    private function createForeignKeys(Table $table)
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
    
    private function createForeignKey(ForeignKey $foreignKey, Table $table)
    {
        $columns = [];
        foreach ($foreignKey->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $referencedColumns = [];
        foreach ($foreignKey->getReferencedColumns() as $column) {
            $referencedColumns[] = $this->escapeString($column);
        }
        $fk = 'CONSTRAINT ' . $this->escapeString($table->getName() . '_' . $foreignKey->getName());
        $fk .= ' FOREIGN KEY (' . implode(',', $columns) . ')';
        $fk .= ' REFERENCES ' . $this->escapeString($foreignKey->getReferencedTable()) . ' (' . implode(',', $referencedColumns) . ')';
        $fk .= ' ON DELETE ' . $foreignKey->getOnDelete() . ' ON UPDATE ' . $foreignKey->getOnUpdate();
        return $fk;
    }
}
