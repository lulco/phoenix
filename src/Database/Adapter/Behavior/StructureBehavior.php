<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter\Behavior;

use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Exception\InvalidArgumentValueException;

trait StructureBehavior
{
    public function getStructure(): Structure
    {
        $database = $this->loadDatabase();
        $structure = new Structure();
        $tables = $this->loadTables($database);
        $columns = $this->loadColumns($database);
        $indexes = $this->loadIndexes($database);
        $foreignKeys = $this->loadForeignKeys($database);
        $uniqueConstraints = $this->loadUniqueConstraints($database);
        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            $migrationTable = $this->createMigrationTable($table);
            $this->addColumns($migrationTable, $columns[$tableName] ?? []);
            $this->addIndexes($migrationTable, $indexes[$tableName] ?? []);
            $this->addForeignKeys($migrationTable, $foreignKeys[$tableName] ?? []);
            $this->addUniqueConstraints($migrationTable, $uniqueConstraints[$tableName] ?? []);
            $migrationTable->create();
            $structure->update($migrationTable);
        }
        return $structure;
    }

    /**
     * @param array<string, string> $table
     */
    protected function createMigrationTable(array $table): MigrationTable
    {
        return new MigrationTable($table['table_name'], false);
    }

    /**
     * @param array<array<string, mixed>> $columns
     */
    protected function addColumns(MigrationTable $migrationTable, array $columns): void
    {
        foreach ($columns as $column) {
            $this->addColumn($migrationTable, $column);
        }
    }

    abstract protected function loadDatabase(): string;

    /**
     * @return array<string[]>
     */
    abstract protected function loadTables(string $database): array;

    /**
     * @return array<string, array<int, mixed>>
     */
    abstract protected function loadColumns(string $database): array;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    abstract protected function loadIndexes(string $database): array;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    abstract protected function loadForeignKeys(string $database): array;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    abstract protected function loadUniqueConstraints(string $database): array;

    /**
     * @param array<string, mixed> $column
     */
    abstract protected function addColumn(MigrationTable $migrationTable, array $column): void;

    /**
     * @param array<string, array<string, mixed>> $indexes
     * @throws InvalidArgumentValueException
     */
    private function addIndexes(MigrationTable $migrationTable, array $indexes): void
    {
        foreach ($indexes as $name => $index) {
            $columns = $index['columns'];
            ksort($columns);
            if ($name === 'PRIMARY') {
                $columns = array_map(function (IndexColumn $column) {
                    return $column->getName();
                }, $columns);
                $migrationTable->addPrimary($columns);
                continue;
            }
            $migrationTable->addIndex(array_values($columns), $index['type'], $index['method'], $name);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $foreignKeys
     */
    private function addForeignKeys(MigrationTable $migrationTable, array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $columns = $foreignKey['columns'];
            ksort($columns);
            $referencedColumns = $foreignKey['referenced_columns'];
            ksort($referencedColumns);
            $migrationTable->addForeignKey(
                array_values($columns),
                $foreignKey['referenced_table'],
                array_values($referencedColumns),
                $foreignKey['on_delete'],
                $foreignKey['on_update']
            );
        }
    }

    /**
     * @param array<string, array<string, mixed>> $uniqueConstraints
     */
    private function addUniqueConstraints(MigrationTable $migrationTable, array $uniqueConstraints): void
    {
        foreach ($uniqueConstraints as $name => $uniqueConstraint) {
            $columns = $uniqueConstraint['columns'];
            ksort($columns);
            $migrationTable->addUniqueConstraint(
                array_values($columns),
                $name
            );
        }
    }
}
