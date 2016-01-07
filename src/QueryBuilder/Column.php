<?php

namespace Phoenix\QueryBuilder;

class Column
{
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TEXT = 'text';
    const TYPE_DATETIME = 'datetime';
    const TYPE_UUID = 'uuid';
    
    private $name;
    
    private $type;
    
    private $allowNull;
    
    private $default;
    
    private $signed;
    
    private $length;
    
    private $decimals;
    
    private $autoincrement;
    
    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param boolean $allowNull default false
     * @param mixed $default default null
     * @param int|null $length length of column, null if you want use default length by column type
     * @param int|null $decimals number of decimals in numeric types (float, double, decimal etc.)
     * @param boolean $signed default true
     * @param boolean $autoincrement default false
     */
    public function __construct(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $length = null,
        $decimals = null,
        $signed = true,
        $autoincrement = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->allowNull = $allowNull;
        $this->default = $default;
        $this->length = $length;
        $this->decimals = $decimals;
        $this->signed = $signed;
        $this->autoincrement = $autoincrement;
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return boolean
     */
    public function allowNull()
    {
        return $this->allowNull;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return boolean
     */
    public function isSigned()
    {
        return $this->signed;
    }

    /**
     * @param mixed $default value to return if length is not set
     * @return mixed
     */
    public function getLength($default = null)
    {
        return $this->length ?: $default;
    }

    /**
     * @return int|null
     */
    public function getDecimals()
    {
        return $this->decimals;
    }
    
    /**
     * @return boolean
     */
    public function isAutoincrement()
    {
        return $this->autoincrement;
    }
}
