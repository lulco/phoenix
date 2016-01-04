<?php

namespace Phoenix\QueryBuilder;

use Exception;

class MysqlQueryBuilder implements QueryBuilderInterface
{
    private $typeMap = [
        'string' => 'varchar(%s)',
        'integer' => 'int(%s)',
    ];
    
    private $defaultLength = [
        'string' => 255,
        'integer' => 11,
    ];
    
    /** @var Table */
    private $table;
    
    public function __construct(Table $table)
    {
        $this->table = $table;
    }
    
    public function create()
    {
        $query = 'CREATE TABLE `' . $this->table->getName() . '` (';
        $columns = [];
        foreach ($this->table->getColumns() as $column) {
            $columns[] = $this->createColumn($column);
        }
        $query .= implode(',', $columns);
        $query .= $this->createPrimaryKey();
        $query .= ') DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;';
        return $query;
    }
    
    private function createColumn(Column $column)
    {
        return $this->createColumnName($column). ' ' . $this->createType($column) . ($column->allowNull() ? '' : ' NOT NULL') . ($column->isAutoincrement() ? ' AUTO_INCREMENT' : '');
    }
    
    private function createColumnName(Column $column)
    {
        return '`' . $column->getName() . '`';
    }
    
    private function createType(Column $column)
    {
        return sprintf($this->remapType($column), $column->getLength($this->defaultLength[$column->getType()]));
    }
    
    private function remapType(Column $column)
    {
        if (!isset($this->typeMap[$column->getType()])) {
            throw new Exception('Type "' . $column->getType() . '" is not allowed');
        }
        return $this->typeMap[$column->getType()];
    }
    
    private function createPrimaryKey()
    {
        if (empty($this->table->getPrimaryColumns())) {
            return '';
        }
        
        $primaryKeys = [];
        foreach ($this->table->getPrimaryColumns() as $name) {
            $primaryKeys[] = $this->createColumnName($this->table->getColumn($name));
        }
        return ',PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
}
