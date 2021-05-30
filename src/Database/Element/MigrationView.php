<?php

namespace Phoenix\Database\Element;

class MigrationView
{
    const ACTION_CREATE = 'create';

    const ACTION_REPLACE = 'replace';

    const ACTION_DROP = 'drop';

    private $name;

    private $columns = [];

    private $sql;

    private $action;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

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

    public function save(): void
    {
        $this->action = self::ACTION_ALTER;
    }

    public function drop(): void
    {
        $this->action = self::ACTION_DROP;
    }

    public function getName(): string
    {
        return $this->name;
    }

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
        return $this->action;
    }
}
