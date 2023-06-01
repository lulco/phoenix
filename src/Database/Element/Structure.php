<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

final class Structure
{
    /** @var Table[] */
    private array $tables = [];

    public function update(MigrationTable $migrationTable): Structure
    {
        if ($migrationTable->getAction() === MigrationTable::ACTION_CREATE) {
            $this->tables[$migrationTable->getName()] = $migrationTable->toTable();
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_DROP) {
            unset($this->tables[$migrationTable->getName()]);
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_RENAME) {
            $table = $this->tables[$migrationTable->getName()];
            /** @var string $newName */
            $newName = $migrationTable->getNewName();
            $table->setName($newName);
            $this->tables[$newName] = $table;
            unset($this->tables[$migrationTable->getName()]);
            foreach ($this->getTables() as $table) {
                foreach ($table->getForeignKeys() as $foreginKey) {
                    if ($foreginKey->getReferencedTable() == $migrationTable->getName()) {
                        $foreginKey->setReferencedTable($newName);
                    }
                }
            }
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_ALTER) {
            $table = $this->tables[$migrationTable->getName()];
            foreach ($migrationTable->getIndexesToDrop() as $index) {
                $table->removeIndex($index);
            }
            foreach ($migrationTable->getForeignKeysToDrop() as $foreignKey) {
                $table->removeForeignKey($foreignKey);
            }
            foreach ($migrationTable->getUniqueConstraintsToDrop() as $uniqueConstraint) {
                $table->removeUniqueConstraint($uniqueConstraint);
            }
            foreach ($migrationTable->getColumnsToChange() as $oldName => $column) {
                $table->changeColumn($oldName, $column);
            }
            foreach ($migrationTable->getColumnsToDrop() as $column) {
                $table->removeColumn($column);
            }
            foreach ($migrationTable->getColumns() as $column) {
                $table->addColumn($column);
            }
            foreach ($migrationTable->getIndexes() as $index) {
                $table->addIndex($index);
            }
            foreach ($migrationTable->getForeignKeys() as $foreignKey) {
                $table->addForeignKey($foreignKey);
            }
            foreach ($migrationTable->getUniqueConstraints() as $uniqueConstraint) {
                $table->addUniqueConstraint($uniqueConstraint);
            }
        }

        return $this;
    }

    public function add(Table $table): Structure
    {
        $this->tables[$table->getName()] = $table;
        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTable(string $tableName): ?Table
    {
        return $this->tables[$tableName] ?? null;
    }
}
