<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

final class IndexColumn
{
    private string $name;

    private IndexColumnSettings $columnSettings;

    /**
     * @param array<string, int|string> $columnSettings
     */
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
