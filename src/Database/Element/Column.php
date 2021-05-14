<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

class Column
{
    const TYPE_STRING = 'string';
    const TYPE_BIT = 'bit';
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
    const TYPE_TIME = 'time';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_UUID = 'uuid';
    const TYPE_JSON = 'json';
    const TYPE_CHAR = 'char';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'double';
    const TYPE_ENUM = 'enum';
    const TYPE_SET = 'set';
    const TYPE_YEAR = 'year';
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
     * @param array<string, mixed> $settings - list of settings, available keys: null, default, length, decimals, signed, autoincrement, after, first, charset, collation, values, comment
     * @throws InvalidArgumentValueException if setting is not allowed
     */
    public function __construct(string $name, string $type, array $settings = [])
    {
        $this->checkType($type);
        $this->name = $name;
        $this->type = $type;
        $this->settings = new ColumnSettings($settings);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSettings(): ColumnSettings
    {
        return $this->settings;
    }

    private function checkType(string $type): void
    {
        $reflectionClass = new ReflectionClass($this);
        $typeConstants = $reflectionClass->getConstants();
        if (!in_array($type, $typeConstants, true)) {
            throw new InvalidArgumentValueException('Type "' . $type . '" is not allowed.');
        }
    }
}
