<?php

namespace Phoenix\QueryBuilder;

use Exception;

class MysqlQueryBuilder implements QueryBuilderInterface
{
    private $typeMap = [
        Column::TYPE_STRING => 'varchar(%s)',
        Column::TYPE_INTEGER => 'int(%s)',
        Column::TYPE_BOOLEAN => 'int(%s)',
        Column::TYPE_TEXT => 'text',
    ];
    
    private $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_INTEGER => 11,
        Column::TYPE_BOOLEAN => 1,
    ];
    
    public function createTable(Table $table)
    {
        $query = 'CREATE TABLE `' . $table->getName() . '` (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column);
        }
        $query .= implode(',', $columns);
        $query .= $this->createPrimaryKey($table);
        $query .= $this->createIndexes($table);
        $query .= ') DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;';
        return $query;
    }
    
    public function dropTable(Table $table)
    {
        return 'DROP TABLE `' . $table->getName() . '`';
    }
    
//    public function alterTable()
//    {
//        ;
//    }
    
    private function createColumn(Column $column)
    {
        $col = $this->createColumnName($column) . ' ' . $this->createType($column);
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        if ($column->getDefault() !== null) {
            $col .= ' DEFAULT ';
            if ($column->getType() == Column::TYPE_INTEGER) {
                $col .= $column->getDefault();
            } elseif ($column->getType() == Column::TYPE_BOOLEAN) {
                $col .= intval($column->getDefault());
            } else {
                $col .= "'" . $column->getDefault() . "'";
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        
        $col .= $column->isAutoincrement() ? ' AUTO_INCREMENT' : '';
        return $col;
    }
    
    private function createColumnName(Column $column)
    {
        return '`' . $column->getName() . '`';
    }
    
    private function createType(Column $column)
    {
        return sprintf($this->remapType($column), $column->getLength(isset($this->defaultLength[$column->getType()]) ? $this->defaultLength[$column->getType()] : null));
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
            $primaryKeys[] = $this->createColumnName($table->getColumn($name));
        }
        return ',PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndexes(Table $table)
    {
        if (empty($table->getIndexes())) {
            return '';
        }
        
        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $columns = [];
            foreach ($index->getColumns() as $column) {
                $columns[] = $this->createColumnName($table->getColumn($column));
            }
            $indexes[] = $index->getType() . ' `' . $index->getName() . '` (' . implode(',', $columns) . ')';
        }
        return ',' . implode(',', $indexes);
    }
}
