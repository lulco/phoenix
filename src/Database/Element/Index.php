<?php

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

class Index
{
    use ParamsCheckerBehavior;

    const TYPE_NORMAL = '';
    const TYPE_UNIQUE = 'UNIQUE';
    const TYPE_FULLTEXT = 'FULLTEXT';

    const METHOD_DEFAULT = '';
    const METHOD_BTREE = 'BTREE';
    const METHOD_HASH = 'HASH';

    private $columns = [];

    private $name;

    private $type;

    private $method;

    /**
     * @param string|array $columns name(s) of column(s)
     * @param string $name name of index
     * @param string $type type of index (unique, fulltext) default ''
     * @param string $method method of index (btree, hash) default ''
     * @throws InvalidArgumentValueException if index type or index method is not allowed
     * @todo change $columns to array only
     */
    public function __construct($columns, string $name, string $type = self::TYPE_NORMAL, string $method = self::METHOD_DEFAULT)
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->inArray($this->type, [self::TYPE_NORMAL, self::TYPE_UNIQUE, self::TYPE_FULLTEXT], 'Index type "' . $type . '" is not allowed');

        $this->method = strtoupper($method);
        $this->inArray($this->method, [self::METHOD_DEFAULT, self::METHOD_BTREE, self::METHOD_HASH], 'Index method "' . $method . '" is not allowed');

        $this->columns = is_array($columns) ? $columns : [$columns];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
