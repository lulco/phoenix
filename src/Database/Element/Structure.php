<?php

namespace Phoenix\Database\Element;

class Structure
{
    /** @var Table[] */
    private $tables = [];

    public function update(MigrationTable $migrationTable): Structure
    {
        return $this->add($migrationTable->toTable());
    }

    public function add(Table $table): Structure
    {
        $this->tables[$table->getName()] = $table;
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
