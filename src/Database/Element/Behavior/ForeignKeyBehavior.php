<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;

trait ForeignKeyBehavior
{
    /** @var ForeignKey[] */
    private $foreignKeys = [];

    /** @var string[] */
    private $foreignKeysToDrop = [];

    /**
     * @param string|string[] $columns
     * @param string $referencedTable
     * @param string|string[] $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return MigrationTable
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
