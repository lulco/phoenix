<?php

namespace Phoenix\QueryBuilder;

class Column
{
    private $name;
    
    private $type;
    
    private $allowNull;
    
    private $default;
    
    private $signed;
    
    private $length;
    
    private $decimals;
    
    private $autoincrement;
    
    public function __construct(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $signed = true,
        $length = null,
        $decimals = null,
        $autoincrement = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->allowNull = $allowNull;
        $this->default = $default;
        $this->signed = $signed;
        $this->length = $length;
        $this->decimals = $decimals;
        $this->autoincrement = $autoincrement;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function allowNull()
    {
        return $this->allowNull;
    }
    
    public function getDefault()
    {
        return $this->default;
    }
    
    public function isSigned()
    {
        return $this->signed;
    }
    
    public function getLength($default = null)
    {
        return $this->length ?: $default;
    }
    
    public function getDecimals()
    {
        return $this->decimals;
    }
    
    public function isAutoincrement()
    {
        return $this->autoincrement;
    }
}
