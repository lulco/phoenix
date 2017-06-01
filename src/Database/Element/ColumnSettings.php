<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

class ColumnSettings
{
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

    private $allowedSettingsValues = [
        self::SETTING_NULL => ['is_bool'],
        self::SETTING_DEFAULT => ['is_null', 'is_numeric', 'is_string', 'is_bool'],
        self::SETTING_LENGTH => ['is_null', 'is_int'],
        self::SETTING_DECIMALS => ['is_null', 'is_int'],
        self::SETTING_SIGNED => ['is_bool'],
        self::SETTING_AUTOINCREMENT => ['is_bool'],
        self::SETTING_AFTER => ['is_null', 'is_string'],
        self::SETTING_FIRST => ['is_bool'],
        self::SETTING_CHARSET => ['is_null', 'is_string'],
        self::SETTING_COLLATION => ['is_null', 'is_string'],
        self::SETTING_VALUES => ['is_null', 'is_array'],
    ];

    private $settings = [];

    /**
     * @param array $settings - list of settings, available keys: null, default, length, decimals, signed, autoincrement, after, first, charset, collation, values
     */
    public function __construct(array $settings = [])
    {
        $this->checkSettings($settings);
        $this->settings = $settings;
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
        return isset($this->settings[self::SETTING_NULL]) ? $this->settings[self::SETTING_NULL] : false;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return isset($this->settings[self::SETTING_DEFAULT]) ? $this->settings[self::SETTING_DEFAULT] : null;
    }

    /**
     * @return boolean
     */
    public function isSigned()
    {
        return isset($this->settings[self::SETTING_SIGNED]) ? $this->settings[self::SETTING_SIGNED] : true;
    }

    /**
     * @param mixed $default value to return if length is not set
     * @return mixed
     */
    public function getLength($default = null)
    {
        return isset($this->settings[self::SETTING_LENGTH]) ? $this->settings[self::SETTING_LENGTH] : $default;
    }

    /**
     * @param integer $default
     * @return int|null
     */
    public function getDecimals($default = null)
    {
        return isset($this->settings[self::SETTING_DECIMALS]) ? $this->settings[self::SETTING_DECIMALS] : $default;
    }

    /**
     * @return boolean
     */
    public function isAutoincrement()
    {
        return isset($this->settings[self::SETTING_AUTOINCREMENT]) ? $this->settings[self::SETTING_AUTOINCREMENT] : false;
    }

    /**
     * @return string|null
     */
    public function getAfter()
    {
        return isset($this->settings[self::SETTING_AFTER]) ? $this->settings[self::SETTING_AFTER] : null;
    }

    /**
     * @return boolean
     */
    public function isFirst()
    {
        return isset($this->settings[self::SETTING_FIRST]) ? $this->settings[self::SETTING_FIRST] : false;
    }

    /**
     * @return string|null
     */
    public function getCharset()
    {
        return isset($this->settings[self::SETTING_CHARSET]) ? $this->settings[self::SETTING_CHARSET] : null;
    }

    /**
     * @return string|null
     */
    public function getCollation()
    {
        return isset($this->settings[self::SETTING_COLLATION]) ? $this->settings[self::SETTING_COLLATION] : null;
    }

    /**
     * @return array|null
     */
    public function getValues()
    {
        return isset($this->settings[self::SETTING_VALUES]) ? $this->settings[self::SETTING_VALUES] : null;
    }

    private function checkSettings($settings)
    {
        $errors = [];
        $reflectionClass = new ReflectionClass($this);
        $settingsConstants = $reflectionClass->getConstants();
        foreach ($settings as $setting => $value) {
            if (!in_array($setting, $settingsConstants)) {
                $errors[] = 'Setting "' . $setting . '" is not allowed.';
            }
            $checkedValue = $this->checkValue($setting, $value);
            if ($checkedValue !== true) {
                $errors[] = $checkedValue;
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentValueException(implode("\n", $errors));
        }
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
