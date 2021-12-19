<?php

declare(strict_types=1);

namespace Phoenix\Comparator;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Table;

final class TableComparator
{
    public function diff(Table $sourceTable, Table $targetTable): ?MigrationTable
    {
        $migrationTable = new MigrationTable($sourceTable->getName());
        $returnMigrationTable = $this->handlePrimaryColumns($migrationTable, $sourceTable, $targetTable);
        $returnMigrationTable = $this->handleColumns($migrationTable, $sourceTable, $targetTable) || $returnMigrationTable;
        $returnMigrationTable = $this->handleIndexes($migrationTable, $sourceTable, $targetTable) || $returnMigrationTable;
        $returnMigrationTable = $this->handleForeignKeys($migrationTable, $sourceTable, $targetTable) || $returnMigrationTable;

        return $returnMigrationTable ? $migrationTable : null;
    }

    private function handlePrimaryColumns(MigrationTable $migrationTable, Table $sourceTable, Table $targetTable): bool
    {
        $sourcePrimaryColumns = $sourceTable->getPrimary();
        $targetPrimaryColumns = $targetTable->getPrimary();
        $primaryColumnsToDrop = array_diff($sourcePrimaryColumns, $targetPrimaryColumns);
        $primaryColumnsToAdd = array_diff($targetPrimaryColumns, $sourcePrimaryColumns);

        $changeMade = false;
        if (!empty($primaryColumnsToDrop)) {
            $migrationTable->dropPrimaryKey();
            $changeMade = true;
        }
        $primaryColumns = [];
        foreach ($primaryColumnsToAdd as $primaryColumnToAdd) {
            /** @var Column $primaryColumn */
            $primaryColumn = $targetTable->getColumn($primaryColumnToAdd);
            $primaryColumns[] = $primaryColumn;
            $changeMade = true;
        }
        if (!empty($primaryColumns)) {
            $migrationTable->addPrimaryColumns($primaryColumns);
            $changeMade = true;
        }
        return $changeMade;
    }

    private function handleColumns(MigrationTable $migrationTable, Table $sourceTable, Table $targetTable): bool
    {
        $sourceColumns = $sourceTable->getColumns();
        $targetColumns = $targetTable->getColumns();
        $columnsToDrop = array_diff(array_keys($sourceColumns), array_keys($targetColumns));
        $columnsToAdd = array_diff_key($targetColumns, $sourceColumns);
        $columnsToChange = $this->findColumnsToChange($sourceTable, $targetTable);

        $sourcePrimaryColumns = $sourceTable->getPrimary();
        $targetPrimaryColumns = $targetTable->getPrimary();
        $primaryColumnsToDrop = array_diff($sourcePrimaryColumns, $targetPrimaryColumns);
        $primaryColumnsToAdd = array_diff($targetPrimaryColumns, $sourcePrimaryColumns);

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

        foreach ($columnsToChange as $columnToChangeName => $columnToChange) {
            $migrationTable->changeColumn($columnToChangeName, $columnToChange->getName(), $columnToChange->getType(), $columnToChange->getSettings()->getSettings());
            $changeMade = true;
        }
        return $changeMade;
    }

    /**
     * @param Table $sourceTable
     * @param Table $targetTable
     * @return array<string, Column>
     */
    private function findColumnsToChange(Table $sourceTable, Table $targetTable): array
    {
        $columnsIntersect = array_intersect(array_keys($sourceTable->getColumns()), array_keys($targetTable->getColumns()));
        $columnsComparator = new ColumnComparator();
        $columnsToChange = [];
        foreach ($columnsIntersect as $columnName) {
            /** @var Column $sourceColumn */
            $sourceColumn = $sourceTable->getColumn($columnName);
            /** @var Column $targetColumn */
            $targetColumn = $targetTable->getColumn($columnName);

            $diffColumn = $columnsComparator->diff($sourceColumn, $targetColumn);
            if ($diffColumn !== null) {
                $columnsToChange[$columnName] = $diffColumn;
            }
        }
        return $columnsToChange;
    }

    private function handleIndexes(MigrationTable $migrationTable, Table $sourceTable, Table $targetTable): bool
    {
        $sourceIndexes = $sourceTable->getIndexes();
        $targetIndexes = $targetTable->getIndexes();

        $indexesToDrop = array_diff(array_keys($sourceIndexes), array_keys($targetIndexes));
        $indexesToAdd = array_diff_key($targetIndexes, $sourceIndexes);

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

    private function handleForeignKeys(MigrationTable $migrationTable, Table $sourceTable, Table $targetTable): bool
    {
        $sourceForeignKeys = $sourceTable->getForeignKeys();
        $targetForeignKeys = $targetTable->getForeignKeys();

        $foreignKeysToDrop = array_diff(array_keys($sourceForeignKeys), array_keys($targetForeignKeys));
        $foreignKeysToAdd = array_diff_key($targetForeignKeys, $sourceForeignKeys);

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
