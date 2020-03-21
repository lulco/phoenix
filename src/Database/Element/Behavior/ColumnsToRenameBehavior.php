<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait ColumnsToRenameBehavior
{
    private $columnsToRename = [];

    public function renameColumn(string $oldName, string $newName): MigrationTable
    {
        $this->columnsToRename[$oldName] = $newName;
        return $this;
    }

    public function getColumnsToRename(): array
    {
        return $this->columnsToRename;
    }
}
