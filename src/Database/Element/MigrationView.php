<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

final class MigrationView
{
    public const ACTION_CREATE = 'create';

    public const ACTION_REPLACE = 'replace';

    public const ACTION_DROP = 'drop';

    private string $name;

    /** @var string[] */
    private array $columns = [];

    private string $sql;

    private string $action;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string[] $columns
     */
    public function columns(array $columns): MigrationView
    {
        $this->columns = $columns;
        return $this;
    }

    public function sql(string $sql): MigrationView
    {
        $this->sql = $sql;
        return $this;
    }

    public function create(): void
    {
        $this->action = self::ACTION_CREATE;
    }

    public function replace(): void
    {
        $this->action = self::ACTION_REPLACE;
    }

    public function drop(): void
    {
        $this->action = self::ACTION_DROP;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getAction(): string
    {
        return $this->action ?: self::ACTION_REPLACE;
    }
}
