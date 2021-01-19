<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Exception\InvalidArgumentValueException;

trait ColumnsToChangeBehavior
{
    /** @var array<string, Column> */
    private $columns = [];

    /** @var array<string, Column> */
    private $columnsToChange = [];

    /**
     * @param string $oldName
     * @param string $newName
     * @param string $type
     * @param array<string, mixed> $settings
     * @return MigrationTable
     * @throws InvalidArgumentValueException
     */
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
     * @return array<string, Column>
     */
    public function getColumnsToChange(): array
    {
        return $this->columnsToChange;
    }
}
