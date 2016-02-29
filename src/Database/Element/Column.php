<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;

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
    
    private $allowedSettings = ['null', 'default', 'length', 'decimals', 'signed', 'autoincrement', 'after', 'first'];
    
    private $allowedSettingsValues = [
        'null' => ['is_bool'],
        'default' => ['is_null', 'is_numeric', 'is_string', 'is_bool'],
        'length' => ['is_null', 'is_int'],
        'decimals' => ['is_null', 'is_int'],
        'signed' => ['is_bool'],
        'autoincrement' => ['is_bool'],
        'after' => ['is_null', 'is_string'],
        'first' => ['is_bool'],
    ];
    
    private $name;
    
    private $type;
    
    private $allowNull;
    
    private $default;
    
    private $length;
    
    private $decimals;
    
    private $signed;
    
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
        $this->checkSettings($settings);
        
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
    
    private function checkSettings($settings)
    {
        $errors = [];
        foreach ($settings as $setting => $value) {
            if (!in_array($setting, $this->allowedSettings)) {
                $errors[] = 'Setting "' . $setting . '" is not allowed.';
            }
            $checkedValue = $this->checkValue($setting, $value);
            if ($checkedValue !== true) {
                $errors[] = $checkedValue;
            }
        }

        if (empty($errors)) {
            return true;
        }
        
        throw new InvalidArgumentValueException(implode("\n", $errors));
    }
    
    private function checkValue($setting, $value)
    {
        if (!isset($this->allowedSettingsValues[$setting])) {
            return true;
        }
        
        foreach ($this->allowedSettingsValues[$setting] as $checkFunction) {
            if (call_user_func($checkFunction, $value) === true) {
                return true;
            }
        }
        return 'Value "' . $value . '" is not allowed for setting "' . $setting . '".';
    }
}
