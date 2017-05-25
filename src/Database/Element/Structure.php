<?php

namespace Phoenix\Database\Element;

use Phoenix\Database\Element\MigrationTable;

class Structure
{
    /** @var Table[] */
    private $tables = [];

    public function update(MigrationTable $migrationTable)
    {
        $this->tables[$migrationTable->getName()] = $migrationTable->toTable();
        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param string $tableName
     * @return Table|null
     */
    public function getTable($tableName)
    {
        return isset($this->tables[$tableName]) ? $this->tables[$tableName] : null;
    }
}
