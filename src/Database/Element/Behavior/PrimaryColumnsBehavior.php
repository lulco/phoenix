<?php

namespace Phoenix\Database\Element\Behavior;

use Closure;
use InvalidArgumentException;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;

trait PrimaryColumnsBehavior
{
    private $primaryColumns = [];

    private $primaryColumnsValuesFunction;

    public function addPrimaryColumns(array $primaryColumns, Closure $primaryColumnsValuesFunction = null): MigrationTable
    {
        foreach ($primaryColumns as $primaryColumn) {
            if (!$primaryColumn instanceof Column) {
                throw new InvalidArgumentException('All primaryColumns have to be instance of "' . Column::class . '"');
            }
        }
        $this->primaryColumns = $primaryColumns;
        $this->primaryColumnsValuesFunction = $primaryColumnsValuesFunction;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getPrimaryColumns(): array
    {
        return $this->primaryColumns;
    }

    public function getPrimaryColumnsValuesFunction(): ?Closure
    {
        return $this->primaryColumnsValuesFunction;
    }
}
