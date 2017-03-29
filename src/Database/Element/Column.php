<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;

class Column
{
    const TYPE_STRING = 'string';
    const TYPE_TINY_INTEGER = 'tinyinteger';
    const TYPE_SMALL_INTEGER = 'smallinteger';
    const TYPE_MEDIUM_INTEGER = 'mediuminteger';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIG_INTEGER = 'biginteger';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_BINARY = 'binary';
    const TYPE_VARBINARY = 'varbinary';
    const TYPE_TINY_TEXT = 'tinytext';
    const TYPE_MEDIUM_TEXT = 'mediumtext';
    const TYPE_TEXT = 'text';
    const TYPE_LONG_TEXT = 'longtext';
    const TYPE_TINY_BLOB = 'tinyblob';
    const TYPE_MEDIUM_BLOB = 'mediumblob';
    const TYPE_BLOB = 'blob';
    const TYPE_LONG_BLOB = 'longblob';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_UUID = 'uuid';
    const TYPE_JSON = 'json';
    const TYPE_CHAR = 'char';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'double';
    const TYPE_ENUM = 'enum';
    const TYPE_SET = 'set';
    const TYPE_POINT = 'point';
    const TYPE_LINE = 'line';
    const TYPE_POLYGON = 'polygon';

    const SETTING_NULL = 'null';
    const SETTING_DEFAULT = 'default';
    const SETTING_LENGTH = 'length';
    const SETTING_DECIMALS = 'decimals';
    const SETTING_SIGNED = 'signed';
    const SETTING_AUTOINCREMENT = 'autoincrement';
    const SETTING_AFTER = 'after';
    const SETTING_FIRST = 'first';
    const SETTING_CHARSET = 'charset';
    const SETTING_COLLATION = 'collation';
    const SETTING_VALUES = 'values';

    private $allowedSettings = [
        self::SETTING_NULL,
        self::SETTING_DEFAULT,
        self::SETTING_LENGTH,
        self::SETTING_DECIMALS,
        self::SETTING_SIGNED,
        self::SETTING_AUTOINCREMENT,
        self::SETTING_AFTER,
        self::SETTING_FIRST,
        self::SETTING_CHARSET,
        self::SETTING_COLLATION,
        self::SETTING_VALUES
    ];

    private $allowedSettingsValues = [
        self::SETTING_NULL => ['is_bool'],
        self::SETTING_DEFAULT => ['is_null', 'is_numeric', 'is_string', 'is_bool'],
        self::SETTING_LENGTH => ['is_null', 'is_int'],
        self::SETTING_DECIMALS => ['is_null', 'is_int'],
        self::SETTING_SIGNED => ['is_bool'],
        self::SETTING_AUTOINCREMENT => ['is_bool'],
        self::SETTING_AFTER => ['is_null', 'is_string'],
        self::SETTING_FIRST => ['is_bool'],
        self::SETTING_CHARSET => ['is_string'],
        self::SETTING_COLLATION => ['is_string'],
        self::SETTING_VALUES => ['is_array'],
    ];

    private $name;

    private $type;

    private $settings;

    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param array $settings - list of settings, available keys: null, default, length, decimals, signed, autoincrement, after, first, charset, collation, values
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

    /**
     * @return array|null
     */
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
