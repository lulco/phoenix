<?php

namespace Phoenix\Database\Element;

class Column
{
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TEXT = 'text';
    const TYPE_DATETIME = 'datetime';
    const TYPE_UUID = 'uuid';
    const TYPE_JSON = 'json';
    const TYPE_CHAR = 'char';
    const TYPE_DECIMAL = 'decimal';
    
    private $name;
    
    private $type;
    
    private $allowNull;
    
    private $default;
    
    private $signed;
    
    private $length;
    
    private $decimals;
    
    private $autoincrement;
    
    private $after;
    
    private $first;
    
    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param array $settings - list of settings, available keys: null, default, length, decimals, signed, autoincrement, after, first
     */
    public function __construct($name, $type, array $settings = [])
    {
        // TODO check allowed settings
        
        $this->name = $name;
        $this->type = $type;
        $this->allowNull = isset($settings['null']) ? $settings['null'] : false;
        $this->default = isset($settings['default']) ? $settings['default'] : null;
        $this->length = isset($settings['length']) ? $settings['length'] : null;
        $this->decimals = isset($settings['decimals']) ? $settings['decimals'] : null;
        $this->signed = isset($settings['signed']) ? $settings['signed'] : true;
        $this->autoincrement = isset($settings['autoincrement']) ? $settings['autoincrement'] : false;
        $this->after = isset($settings['after']) ? $settings['after'] : null;
        $this->first = isset($settings['first']) ? $settings['first'] : false;
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
     * @param integer $default
     * @return int|null
     */
    public function getDecimals($default = null)
    {
        return $this->decimals ?: $default;
    }
    
    /**
     * @return boolean
     */
    public function isAutoincrement()
    {
        return $this->autoincrement;
    }
    
    /**
     * @return string|null
     */
    public function getAfter()
    {
        return $this->after;
    }
    
    /**
     * @return boolean
     */
    public function isFirst()
    {
        return $this->first;
    }
}
