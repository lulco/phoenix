<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;

class Column
{
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIG_INTEGER = 'biginteger';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TEXT = 'text';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_UUID = 'uuid';
    const TYPE_JSON = 'json';
    const TYPE_CHAR = 'char';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_FLOAT = 'float';
    const TYPE_ENUM = 'enum';
    const TYPE_SET = 'set';

    private $allowedSettings = ['null', 'default', 'length', 'decimals', 'signed', 'autoincrement', 'after', 'first', 'charset', 'collation', 'values'];

    private $allowedSettingsValues = [
        'null' => ['is_bool'],
        'default' => ['is_null', 'is_numeric', 'is_string', 'is_bool'],
        'length' => ['is_null', 'is_int'],
        'decimals' => ['is_null', 'is_int'],
        'signed' => ['is_bool'],
        'autoincrement' => ['is_bool'],
        'after' => ['is_null', 'is_string'],
        'first' => ['is_bool'],
        'charset' => ['is_string'],
        'collation' => ['is_string'],
        'values' => ['is_array'],
    ];

    private $name;

    private $type;

    private $settings;

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
        $this->settings = $settings;
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
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return boolean
     */
    public function allowNull()
    {
        return isset($this->settings['null']) ? $this->settings['null'] : false;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return isset($this->settings['default']) ? $this->settings['default'] : null;
    }

    /**
     * @return boolean
     */
    public function isSigned()
    {
        return isset($this->settings['signed']) ? $this->settings['signed'] : true;
    }

    /**
     * @param mixed $default value to return if length is not set
     * @return mixed
     */
    public function getLength($default = null)
    {
        return isset($this->settings['length']) ? $this->settings['length'] : $default;
    }

    /**
     * @param integer $default
     * @return int|null
     */
    public function getDecimals($default = null)
    {
        return isset($this->settings['decimals']) ? $this->settings['decimals'] : $default;
    }

    /**
     * @return boolean
     */
    public function isAutoincrement()
    {
        return isset($this->settings['autoincrement']) ? $this->settings['autoincrement'] : false;
    }

    /**
     * @return string|null
     */
    public function getAfter()
    {
        return isset($this->settings['after']) ? $this->settings['after'] : null;
    }

    /**
     * @return boolean
     */
    public function isFirst()
    {
        return isset($this->settings['first']) ? $this->settings['first'] : false;
    }

    /**
     * @return string|null
     */
    public function getCharset()
    {
        return isset($this->settings['charset']) ? $this->settings['charset'] : null;
    }

    /**
     * @return string|null
     */
    public function getCollation()
    {
        return isset($this->settings['collation']) ? $this->settings['collation'] : null;
    }

    public function getValues()
    {
        return isset($this->settings['values']) ? $this->settings['values'] : null;
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
