<?php

namespace Comparator;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Table;

class TableComparator
{
    public function diff(Table $sourceTable, Table $targetTable): ?MigrationTable
    {
        $sourceColumns = $sourceTable->getColumns();
        $targetColumns = $targetTable->getColumns();

        $sourcePrimaryColumns = $sourceTable->getPrimary();
        $targetPrimaryColumns = $targetTable->getPrimary();
        $primaryColumnsToDrop = array_diff($sourcePrimaryColumns, $targetPrimaryColumns);
        $primaryColumnsToAdd = array_diff($targetPrimaryColumns, $sourcePrimaryColumns);

        $columnsToDrop = array_diff(array_keys($sourceColumns), array_keys($targetColumns));
        $columnsToAdd = array_diff_key($targetColumns, $sourceColumns);
        $columnsIntersect = array_intersect(array_keys($sourceColumns), array_keys($targetColumns));

        $columnsComparator = new ColumnComparator();
        $columnsToChange = [];
        foreach ($columnsIntersect as $columnName) {
            $diffColumn = $columnsComparator->diff($sourceTable->getColumn($columnName), $targetTable->getColumn($columnName));
            if ($diffColumn !== null) {
                $columnsToChange[$columnName] = $diffColumn;
            }
        }

        $sourceIndexes = $sourceTable->getIndexes();
        $targetIndexes = $targetTable->getIndexes();

        $indexesToDrop = array_diff(array_keys($sourceIndexes), array_keys($targetIndexes));
        $indexesToAdd = array_diff_key($targetIndexes, $sourceIndexes);

        $sourceForeignKeys = $sourceTable->getForeignKeys();
        $targetForeignKeys = $targetTable->getForeignKeys();

        $foreignKeysToDrop = array_diff(array_keys($sourceForeignKeys), array_keys($targetForeignKeys));
        $foreignKeysToAdd = array_diff_key($targetForeignKeys, $sourceForeignKeys);

        $returnMigrationTable = false;

        $migrationTable = new MigrationTable($sourceTable->getName());
        $returnMigrationTable = $this->handlePrimaryColumns($migrationTable, $primaryColumnsToDrop, $primaryColumnsToAdd, $targetTable) || $returnMigrationTable;
        $returnMigrationTable = $this->handleColumns($migrationTable, $primaryColumnsToDrop, $primaryColumnsToAdd, $columnsToDrop, $columnsToAdd, $columnsToChange) || $returnMigrationTable;
        $returnMigrationTable = $this->handleIndexes($migrationTable, $indexesToDrop, $indexesToAdd) || $returnMigrationTable;
        $returnMigrationTable = $this->handleForeignKeys($migrationTable, $foreignKeysToDrop, $foreignKeysToAdd) || $returnMigrationTable;

        return $returnMigrationTable ? $migrationTable : null;
    }

    private function handlePrimaryColumns(MigrationTable $migrationTable, array $primaryColumnsToDrop, array $primaryColumnsToAdd, Table $targetTable): bool
    {
        $changeMade = false;
        if (!empty($primaryColumnsToDrop)) {
            $migrationTable->dropPrimaryKey();
            $changeMade = true;
        }
        $primaryColumns = [];
        foreach ($primaryColumnsToAdd as $primaryColumnToAdd) {
            $primaryColumns[] = $targetTable->getColumn($primaryColumnToAdd);
            $changeMade = true;
        }
        if (!empty($primaryColumns)) {
            $migrationTable->addPrimaryColumns($primaryColumns);
            $changeMade = true;
        }
        return $changeMade;
    }

    private function handleColumns(MigrationTable $migrationTable, array $primaryColumnsToDrop, array $primaryColumnsToAdd, array $columnsToDrop, array $columnsToAdd, array $columnsToChange): bool
    {
        $changeMade = false;
        foreach ($columnsToDrop as $columnToDrop) {
            if (in_array($columnToDrop, $primaryColumnsToDrop, true)) {
                continue;
            }
            $migrationTable->dropColumn($columnToDrop);
            $changeMade = true;
        }

        /** @var Column $columnToAdd */
        foreach ($columnsToAdd as $columnToAdd) {
            if (in_array($columnToAdd->getName(), $primaryColumnsToAdd, true)) {
                continue;
            }
            $migrationTable->addColumn($columnToAdd->getName(), $columnToAdd->getType(), $columnToAdd->getSettings()->getSettings());
            $changeMade = true;
        }

        /** @var Column $columnToChange */
        foreach ($columnsToChange as $columnToChangeName => $columnToChange) {
            $migrationTable->changeColumn($columnToChangeName, $columnToChange->getName(), $columnToChange->getType(), $columnToChange->getSettings()->getSettings());
            $changeMade = true;
        }
        return $changeMade;
    }

    private function handleIndexes(MigrationTable $migrationTable, array $indexesToDrop, array $indexesToAdd): bool
    {
        $changeMade = false;
        foreach ($indexesToDrop as $indexToDrop) {
            $migrationTable->dropIndexByName($indexToDrop);
            $changeMade = true;
        }

        /** @var Index $indexToAdd */
        foreach ($indexesToAdd as $indexToAdd) {
            $migrationTable->addIndex($indexToAdd->getColumns(), $indexToAdd->getType(), $indexToAdd->getMethod(), $indexToAdd->getName());
            $changeMade = true;
        }
        return $changeMade;
    }

    private function handleForeignKeys(MigrationTable $migrationTable, array $foreignKeysToDrop, array $foreignKeysToAdd): bool
    {
        $changeMade = false;
        foreach ($foreignKeysToDrop as $foreignKeyToDrop) {
            $migrationTable->dropForeignKey($foreignKeyToDrop);
            $changeMade = true;
        }

        /** @var ForeignKey $foreignKeyToAdd */
        foreach ($foreignKeysToAdd as $foreignKeyToAdd) {
            $migrationTable->addForeignKey($foreignKeyToAdd->getColumns(), $foreignKeyToAdd->getReferencedTable(), $foreignKeyToAdd->getReferencedColumns(), $foreignKeyToAdd->getOnDelete(), $foreignKeyToAdd->getOnUpdate());
            $changeMade = true;
        }
        return $changeMade;
    }
}
