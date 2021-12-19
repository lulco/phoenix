<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

final class Index
{
    use ParamsCheckerBehavior;

    public const TYPE_NORMAL = '';
    public const TYPE_UNIQUE = 'UNIQUE';
    public const TYPE_FULLTEXT = 'FULLTEXT';

    public const METHOD_DEFAULT = '';
    public const METHOD_BTREE = 'BTREE';
    public const METHOD_HASH = 'HASH';

    /** @var IndexColumn[] */
    private array $columns = [];

    private string $name;

    private string $type;

    private string $method;

    /**
     * @param IndexColumn[] $columns
     * @param string $name
     * @param string $type
     * @param string $method
     * @throws InvalidArgumentValueException if index type or index method is not allowed
     */
    public function __construct(array $columns, string $name, string $type = self::TYPE_NORMAL, string $method = self::METHOD_DEFAULT)
    {
        $this->columns = $columns;
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->inArray($this->type, [self::TYPE_NORMAL, self::TYPE_UNIQUE, self::TYPE_FULLTEXT], 'Index type "' . $type . '" is not allowed');

        $this->method = strtoupper($method);
        $this->inArray($this->method, [self::METHOD_DEFAULT, self::METHOD_BTREE, self::METHOD_HASH], 'Index method "' . $method . '" is not allowed');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return IndexColumn[]
     */
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
