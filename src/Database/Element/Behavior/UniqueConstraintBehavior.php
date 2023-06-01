<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\UniqueConstraint;

trait UniqueConstraintBehavior
{
    /** @var UniqueConstraint[] */
    private array $uniqueConstraints = [];

    /** @var string[] */
    private array $uniqueConstraintsToDrop = [];

    /**
     * One should be aware that for postgres there's no need to manually create indexes on unique columns.
     * Doing so would just duplicate the automatically-created index.
     *
     * @param string|string[] $columns
     * @param string $name
     * @return MigrationTable
     */
    public function addUniqueConstraint($columns, string $name): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->uniqueConstraints[] = new UniqueConstraint($columns, $name);

        return $this;
    }

    /**
     * @return UniqueConstraint[]
     */
    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    public function dropUniqueConstraint(string $name): MigrationTable
    {
        $this->uniqueConstraintsToDrop[] = $name;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getUniqueConstraintsToDrop(): array
    {
        return $this->uniqueConstraintsToDrop;
    }
}
