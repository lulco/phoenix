<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\StructureException;
use Phoenix\Database\Element\MigrationTable;

class Structure
{
    /** @var Table[] */
    private $tables = [];

    /**
     * @param MigrationTable $migrationTable
     * @return MigrationTable
     * @throws StructureException - if some error occured - e.g. table already exists
     */
    public function prepare(MigrationTable $migrationTable)
    {
        switch ($migrationTable->getAction()) {
            case MigrationTable::ACTION_CREATE:
                if ($this->tableExists($migrationTable->getName())) {
                    throw new StructureException('Table "' . $migrationTable->getName() . '" already exists');
                }
                break;
            case MigrationTable::ACTION_DROP:
                if (!$this->tableExists($migrationTable->getName())) {
                    throw new StructureException('Table "' . $migrationTable->getName() . '" doesn\'t exist');
                }
                // TODO check foreign keys to this table
                break;
            case MigrationTable::ACTION_ALTER:
                if (!$this->tableExists($migrationTable->getName())) {
                    throw new StructureException('Table "' . $migrationTable->getName() . '" doesn\'t exist');
                }
                $table = $this->getTable($migrationTable->getName());
                foreach ($migrationTable->getColumns() as $column) {
                    if ($table->getColumn($column->getName())) {
                        throw new StructureException('Column "' . $column->getName() . '" already exists in table "' . $migrationTable->getName() . '"');
                    }
                }
                foreach ($migrationTable->getColumnsToDrop() as $columnName) {
                    if (!$table->getColumn($columnName)) {
                        throw new StructureException('Column "' . $columnName . '" doesn\'t exist in table "' . $migrationTable->getName() . '"');
                    }
                }
                foreach ($migrationTable->getColumnsToChange() as $oldName => $column) {
                    if (!$table->getColumn($oldName)) {
                        throw new StructureException('Column "' . $oldName . '" doesn\'t exist in table "' . $migrationTable->getName() . '"');
                    }
                    if ($column->getName() != $oldName && $table->getColumn($column->getName())) {
                        throw new StructureException('Column "' . $column->getName() . '" already exists in table "' . $migrationTable->getName() . '"');
                    }
                }
                // TODO foreign keys, indexes, etc.
                break;
            case MigrationTable::ACTION_RENAME:
                if (!$this->tableExists($migrationTable->getName())) {
                    throw new StructureException('Table "' . $migrationTable->getName() . '" doesn\'t exist');
                }
                if ($this->tableExists($migrationTable->getNewName())) {
                    throw new StructureException('Table "' . $migrationTable->getNewName() . '" already exists');
                }
                break;
        }

        return $migrationTable;
    }

    public function update(MigrationTable $migrationTable)
    {
        if ($migrationTable->getAction() === MigrationTable::ACTION_CREATE) {
            $this->tables[$migrationTable->getName()] = $migrationTable->toTable();
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_DROP) {
            unset($this->tables[$migrationTable->getName()]);
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_RENAME) {
            $this->tables[$migrationTable->getNewName()] = $this->tables[$migrationTable->getName()];
            unset($this->tables[$migrationTable->getName()]);
            // todo zmenit tabulku vo foreign keys vsade kde sa pouziva stara tabulka
        } elseif ($migrationTable->getAction() === MigrationTable::ACTION_ALTER) {
            $table = $this->tables[$migrationTable->getName()];
            foreach ($migrationTable->getIndexesToDrop() as $index) {
                $table->removeIndex($index);
            }
            foreach ($migrationTable->getForeignKeysToDrop() as $foreignKey) {
                $table->removeForeignKey($foreignKey);
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
        }
        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param string $tableName
     * @return Table|null
     */
    public function getTable($tableName)
    {
        return $this->tableExists($tableName) ? $this->tables[$tableName] : null;
    }

    /**
     * @param string $tableName
     * @return boolean
     */
    public function tableExists($tableName)
    {
        return isset($this->tables[$tableName]);
    }
}
