<?php

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

class ForeignKey
{
    use ParamsCheckerBehavior;
    
    const DEFAULT_ACTION = '';
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
    public function __construct($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = self::DEFAULT_ACTION, $onUpdate = self::DEFAULT_ACTION)
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
        $this->inArray($this->onDelete, [self::DEFAULT_ACTION, self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL], 'Action "' . $onDelete . '" is not allowed on delete');
        
        $this->onUpdate = strtoupper($onUpdate);
        $this->inArray($this->onUpdate, [self::DEFAULT_ACTION, self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL], 'Action "' . $onUpdate . '" is not allowed on update');
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return implode('_', $this->columns);
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * @return string
     */
    public function getReferencedTable()
    {
        return $this->referencedTable;
    }

    /**
     * @return array
     */
    public function getReferencedColumns()
    {
        return $this->referencedColumns;
    }

    /**
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }
}
