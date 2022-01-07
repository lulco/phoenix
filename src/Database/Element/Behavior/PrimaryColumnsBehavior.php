<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Closure;
use InvalidArgumentException;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;

trait PrimaryColumnsBehavior
{
    /** @var Column[] */
    private array $primaryColumns = [];

    private ?Closure $primaryColumnsValuesFunction = null;

    private ?int $dataChunkSize = null;

    /**
     * @param Column[] $primaryColumns
     * @param Closure|null $primaryColumnsValuesFunction
     * @param int|null $dataChunkSize
     * @return MigrationTable
     */
    public function addPrimaryColumns(array $primaryColumns, ?Closure $primaryColumnsValuesFunction = null, ?int $dataChunkSize = null): MigrationTable
    {
        foreach ($primaryColumns as $primaryColumn) {
            if (!$primaryColumn instanceof Column) {
                throw new InvalidArgumentException('All primaryColumns have to be instance of "' . Column::class . '"');
            }
        }
        $this->primaryColumns = $primaryColumns;
        $this->primaryColumnsValuesFunction = $primaryColumnsValuesFunction;
        $this->dataChunkSize = $dataChunkSize;
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

    public function getDataChunkSize(): ?int
    {
        return $this->dataChunkSize;
    }
}
