<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

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

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var ColumnSettings */
    private $settings;

    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param array $settings - list of settings, available keys: null, default, length, decimals, signed, autoincrement, after, first, charset, collation, values, comment
     */
    public function __construct($name, $type, array $settings = [])
    {
        $this->checkType($type);
        $this->name = $name;
        $this->type = $type;
        $this->settings = new ColumnSettings($settings);
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
     * @return ColumnSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    private function checkType($type)
    {
        $reflectionClass = new ReflectionClass($this);
        $typeConstants = $reflectionClass->getConstants();
        if (!in_array($type, $typeConstants)) {
            throw new InvalidArgumentValueException('Type "' . $type . '" is not allowed.');
        }
    }
}
