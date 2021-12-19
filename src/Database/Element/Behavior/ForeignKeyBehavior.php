<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Exception\InvalidArgumentValueException;

trait ForeignKeyBehavior
{
    /** @var ForeignKey[] */
    private array $foreignKeys = [];

    /** @var string[] */
    private array $foreignKeysToDrop = [];

    /**
     * @param string|string[] $columns
     * @param string|string[] $referencedColumns
     * @throws InvalidArgumentValueException
     */
    public function addForeignKey($columns, string $referencedTable, $referencedColumns = ['id'], string $onDelete = ForeignKey::DEFAULT_ACTION, string $onUpdate = ForeignKey::DEFAULT_ACTION): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        if (!is_array($referencedColumns)) {
            $referencedColumns = [$referencedColumns];
        }
        $this->foreignKeys[] = new ForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate);
        return $this;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @param string|string[] $columns
     */
    public function dropForeignKey($columns): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->foreignKeysToDrop[] = implode('_', $columns);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getForeignKeysToDrop(): array
    {
        return $this->foreignKeysToDrop;
    }
}
