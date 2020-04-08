<?php

namespace Phoenix\Database\Element;

class IndexColumn
{
    /** @var string */
    private $name;

    /** @var IndexColumnSettings */
    private $columnSettings;

    public function __construct(string $name, array $columnSettings = [])
    {
        $this->name = $name;
        $this->columnSettings = new IndexColumnSettings($columnSettings);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSettings(): IndexColumnSettings
    {
        return $this->columnSettings;
    }
}
