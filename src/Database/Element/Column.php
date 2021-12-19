<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

final class Column
{
    public const TYPE_STRING = 'string';
    public const TYPE_BIT = 'bit';
    public const TYPE_TINY_INTEGER = 'tinyinteger';
    public const TYPE_SMALL_INTEGER = 'smallinteger';
    public const TYPE_MEDIUM_INTEGER = 'mediuminteger';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BIG_INTEGER = 'biginteger';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_BINARY = 'binary';
    public const TYPE_VARBINARY = 'varbinary';
    public const TYPE_TINY_TEXT = 'tinytext';
    public const TYPE_MEDIUM_TEXT = 'mediumtext';
    public const TYPE_TEXT = 'text';
    public const TYPE_LONG_TEXT = 'longtext';
    public const TYPE_TINY_BLOB = 'tinyblob';
    public const TYPE_MEDIUM_BLOB = 'mediumblob';
    public const TYPE_BLOB = 'blob';
    public const TYPE_LONG_BLOB = 'longblob';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_TIMESTAMP = 'timestamp';
    public const TYPE_TIMESTAMP_TZ = 'timestamptz';
    public const TYPE_UUID = 'uuid';
    public const TYPE_JSON = 'json';
    public const TYPE_CHAR = 'char';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_ENUM = 'enum';
    public const TYPE_SET = 'set';
    public const TYPE_YEAR = 'year';
    public const TYPE_POINT = 'point';
    public const TYPE_LINE = 'line';
    public const TYPE_POLYGON = 'polygon';

    private string $name;

    private string $type;

    private ColumnSettings $settings;

    /**
     * @param array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string} $settings - list of settings
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
