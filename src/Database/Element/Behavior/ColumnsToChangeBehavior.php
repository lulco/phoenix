<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;

trait ColumnsToChangeBehavior
{
    private $columns = [];

    private $columnsToChange = [];

    public function changeColumn(string $oldName, string $newName, string $type, array $settings = []): MigrationTable
    {
        $newColumn = new Column($newName, $type, $settings);
        if (isset($this->columns[$oldName])) {
            $this->columns[$oldName] = $newColumn;
            return $this;
        }

        $this->columnsToChange[$oldName] = $newColumn;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumnsToChange(): array
    {
        return $this->columnsToChange;
    }
}
