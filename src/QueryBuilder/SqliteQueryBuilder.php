<?php

namespace Phoenix\QueryBuilder;

use Exception;

class SqliteQueryBuilder implements QueryBuilderInterface
{
    private $typeMap = [
        Column::TYPE_STRING => 'TEXT',
        Column::TYPE_INTEGER => 'INTEGER',
        Column::TYPE_BOOLEAN => 'INTEGER',
        Column::TYPE_TEXT => 'TEXT',
        Column::TYPE_DATETIME => 'TEXT',
        Column::TYPE_UUID => 'INTEGER',
    ];
    
    /**
     * generates create table query for mysql
     * @param Table $table
     * @return string
     *
     * @todo we need to return array of queries, because of indexes, so we have to refactor this builder to return array
     */
    public function createTable(Table $table)
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
        
        if (empty($table->getIndexes())) {
            return $query;
        }
        
        $queries = [
            $query,
        ];
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }
        return $queries;
    }
    
    /**
     * generates drop table query for mysql
     * @param Table $table
     * @return string
     */
    public function dropTable(Table $table)
    {
        return 'DROP TABLE ' . $this->escapeString($table->getName());
    }
    
//    public function alterTable()
//    {
//        ;
//    }
    
    private function createColumn(Column $column, Table $table)
    {
        $col = $this->createColumnName($column) . ' ' . $this->createType($column);
        $col .= $column->isAutoincrement() && in_array($column->getName(), $table->getPrimaryColumns()) ? ' PRIMARY KEY AUTOINCREMENT' : '';
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        if ($column->getDefault() !== null && $column->getDefault() !== '') {
            $col .= ' DEFAULT ';
            if ($column->getType() == Column::TYPE_INTEGER) {
                $col .= intval($column->getDefault());
            } elseif ($column->getType() == Column::TYPE_BOOLEAN) {
                $col .= intval($column->getDefault());
            } else {
                $col .= $column->getDefault();
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        return $col;
    }
    
    
    private function createType(Column $column)
    {
        return $this->remapType($column);
    }
    
    private function remapType(Column $column)
    {
        if (!isset($this->typeMap[$column->getType()])) {
            throw new Exception('Type "' . $column->getType() . '" is not allowed');
        }
        return $this->typeMap[$column->getType()];
    }
    
    private function createPrimaryKey(Table $table)
    {
        if (empty($table->getPrimaryColumns())) {
            return '';
        }
        
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $column = $table->getColumn($name);
            if (!$column->isAutoincrement()) {
                $primaryKeys[] = $this->createColumnName($column);
            }
        }
        if (empty($primaryKeys)) {
            return '';
        }
        return ',PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndex(Index $index, Table $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->createColumnName($table->getColumn($column));
        }
        $query = 'CREATE ' . $index->getType() . ' ' . $this->escapeString($table->getName() . '_' . $index->getName()) . ' ON ' . $this->escapeString($table->getName()) . ' (' . implode(',', $columns) . ');';
        return $query;
    }
    
    private function createForeignKeys(Table $table)
    {
        if (empty($table->getForeignKeys())) {
            return '';
        }
        
        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $columns = [];
            foreach ($foreignKey->getColumns() as $column) {
                $columns[] = $this->createColumnName($table->getColumn($column));
            }
            $referencedColumns = [];
            foreach ($foreignKey->getReferencedColumns() as $column) {
                $referencedColumns[] = $this->escapeString($column);
            }
            $fk = 'CONSTRAINT ' . $this->escapeString($table->getName() . '_' . $foreignKey->getName());
            $fk .= ' FOREIGN KEY (' . implode(',', $columns) . ')';
            $fk .= ' REFERENCES ' . $this->escapeString($foreignKey->getReferencedTable()) . ' (' . implode(',', $referencedColumns) . ')';
            $fk .= ' ON DELETE ' . $foreignKey->getOnDelete() . ' ON UPDATE ' . $foreignKey->getOnUpdate();
            $foreignKeys[] = $fk;
        }
        return ',' . implode(',', $foreignKeys);
    }
    
    private function createColumnName(Column $column)
    {
        return $this->escapeString($column->getName());
    }
    
    private function escapeString($string)
    {
        return '"' . $string . '"';
    }
}
