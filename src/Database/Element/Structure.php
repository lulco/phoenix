<?php

namespace Phoenix\Database\Element;

use Phoenix\Database\Element\MigrationTable;

class Structure
{
    /** @var Table[] */
    private $tables = [];

    public function update(MigrationTable $migrationTable): Structure
    {
        $this->tables[$migrationTable->getName()] = $migrationTable->toTable();
        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTable(string $tableName): ?Table
    {
        return $this->tables[$tableName] ?? null;
    }
}
