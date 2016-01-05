<?php

namespace Phoenix\QueryBuilder;

use InvalidArgumentException;

class Index
{
    const TYPE_NORMAL = '';
    const TYPE_UNIQUE = 'UNIQUE';
    const TYPE_FULLTEXT = 'FULLTEXT';
    
    private $columns = [];
    
    private $type;
    
    public function __construct($columns, $type = self::TYPE_NORMAL)
    {
        $this->type = strtoupper($type);
        if (!in_array($this->type, [self::TYPE_NORMAL, self::TYPE_UNIQUE, self::TYPE_FULLTEXT])) {
            throw new InvalidArgumentException('Index type "' . $type . '" is not allowed');
        }
        
        if (is_array($columns)) {
            $this->columns = $columns;
        } elseif (is_string($columns)) {
            $this->columns[] = $columns;
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
    
    public function getType()
    {
        return $this->type ? $this->type . ' INDEX' : 'INDEX';
    }
}
