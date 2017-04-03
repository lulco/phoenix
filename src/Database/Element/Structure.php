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
     * @throws StructureException - if some error occured - e.g. table which should be created already exists
     */
    public function prepare(MigrationTable $migrationTable)
    {
        if ($migrationTable->getAction() == MigrationTable::ACTION_CREATE) {
            $this->checkTableAlreadyExists($migrationTable->getName());
            foreach ($migrationTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getReferencedTable() == $migrationTable->getName()) {
                    continue;
                }
                $this->checkTableNotExists($foreignKey->getReferencedTable());
            }
        } elseif ($migrationTable->getAction() == MigrationTable::ACTION_DROP) {
            $this->checkTableNotExists($migrationTable->getName());
            foreach ($this->getTables() as $table) {
                if ($table->getName() == $migrationTable->getName()) {
                    continue;
                }
                foreach ($table->getForeignKeys() as $foreginKey) {
                    if ($foreginKey->getReferencedTable() == $migrationTable->getName()) {
                        throw new StructureException('Table "' . $migrationTable->getName() . '" is referenced in foreign key "' . $foreginKey->getName() . '" in table "' . $table->getName() . '"');
                    }
                }
            }
        } elseif ($migrationTable->getAction() == MigrationTable::ACTION_ALTER) {
            $this->checkTableNotExists($migrationTable->getName());
            $table = $this->getTable($migrationTable->getName());
            foreach ($migrationTable->getColumns() as $column) {
                $this->checkColumnAlreadyExists($table, $column->getName());
            }
            foreach ($migrationTable->getColumnsToDrop() as $columnName) {
                $this->checkColumnNotExists($table, $columnName);
            }
            foreach ($migrationTable->getColumnsToChange() as $oldName => $column) {
                $this->checkColumnNotExists($table, $oldName);
                if ($column->getName() != $oldName) {
                    $this->checkColumnAlreadyExists ($table, $column->getName());
                }
            }
            foreach ($migrationTable->getForeignKeys() as $foreignKey) {
                if ($table->getForeignKey($foreignKey->getName())) {
                    throw new StructureException("Foreign key '{$foreignKey->getName()}' already exists in table '{$table->getName()}'");
                }
            }
            foreach ($migrationTable->getForeignKeysToDrop() as $foreignKeyName) {
                if (!$table->getForeignKey($foreignKeyName)) {
                    throw new StructureException("Foreign key '$foreignKeyName' doesn't exist in table '{$table->getName()}'");
                }
            }
            foreach ($migrationTable->getIndexes() as $index) {
                if ($table->getIndex($index->getName())) {
                    throw new StructureException("Index '{$index->getName()}' already exists in table '{$table->getName()}'");
                }
            }
            foreach ($migrationTable->getIndexesToDrop() as $indexName) {
                if (!$table->getIndex($indexName)) {
                    throw new StructureException("Index '$indexName' doesn't exist in table '{$table->getName()}'");
                }
            }
        } elseif ($migrationTable->getAction() == MigrationTable::ACTION_RENAME) {
            $this->checkTableNotExists($migrationTable->getName());
            $this->checkTableAlreadyExists($migrationTable->getNewName());
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
            $table = $this->tables[$migrationTable->getName()];
            $table->setName($migrationTable->getNewName());
            $this->tables[$migrationTable->getNewName()] = $table;
            unset($this->tables[$migrationTable->getName()]);
            foreach ($this->getTables() as $table) {
                foreach ($table->getForeignKeys() as $foreginKey) {
                    if ($foreginKey->getReferencedTable() == $migrationTable->getName()) {
                        $foreginKey->setReferencedTable($migrationTable->getNewName());
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

    private function checkTableAlreadyExists($tableName)
    {
        if ($this->tableExists($tableName)) {
            throw new StructureException('Table "' . $tableName . '" already exists');
        }
    }

    private function checkTableNotExists($tableName)
    {
        if (!$this->tableExists($tableName)) {
            throw new StructureException('Table "' . $tableName . '" doesn\'t exist');
        }
    }

    private function checkColumnAlreadyExists(Table $table, $columnName)
    {
        if ($table->getColumn($columnName)) {
            throw new StructureException('Column "' . $columnName . '" already exists in table "' . $table->getName() . '"');
        }
    }

    private function checkColumnNotExists(Table $table, $columnName)
    {
        if (!$table->getColumn($columnName)) {
            throw new StructureException('Column "' . $columnName . '" doesn\'t exist in table "' . $table->getName() . '"');
        }
    }
}
