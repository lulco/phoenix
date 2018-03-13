<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait ColumnsToDropBehavior
{
    private $columnsToDrop = [];

    public function dropColumn(string $name): MigrationTable
    {
        $this->columnsToDrop[] = $name;
        return $this;
    }

    public function getColumnsToDrop(): array
    {
        return $this->columnsToDrop;
    }
}
