<?php

namespace Phoenix\QueryBuilder;

use Phoenix\Exception\InvalidArgumentValueException;

class ForeignKey
{
    const RESTRICT = 'RESTRICT';
    const NO_ACTION = 'NO ACTION';
    const CASCADE = 'CASCADE';
    const SET_NULL = 'SET NULL';
    
    private $columns = [];
    
    private $referencedTable;
    
    private $referencedColumns;
    
    private $onDelete;
    
    private $onUpdate;
    
    /**
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @throws InvalidArgumentValueException
     */
    public function __construct($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = ForeignKey::RESTRICT, $onUpdate = ForeignKey::RESTRICT)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->columns = $columns;
        $this->referencedTable = $referencedTable;
        
        if (!is_array($referencedColumns)) {
            $referencedColumns = [$referencedColumns];
        }
        $this->referencedColumns = $referencedColumns;
        
        $this->onDelete = strtoupper($onDelete);
        if (!in_array($this->onDelete, [self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL])) {
            throw new InvalidArgumentValueException('Action "' . $onDelete . '" is not allowed on delete');
        }
        $this->onUpdate = strtoupper($onUpdate);
        if (!in_array($this->onUpdate, [self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL])) {
            throw new InvalidArgumentValueException('Action "' . $onUpdate . '" is not allowed on update');
        }
    }
    
    public function getName()
    {
        return implode('_', $this->columns);
    }
    
    public function getColumns()
    {
        return $this->columns;
    }
    
    public function getReferencedTable()
    {
        return $this->referencedTable;
    }
    
    public function getReferencedColumns()
    {
        return $this->referencedColumns;
    }
    
    public function getOnDelete()
    {
        return $this->onDelete;
    }
    
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }
}
