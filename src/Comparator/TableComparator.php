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
        $indexesToChange = [];

        $sourceForeignKeys = $sourceTable->getForeignKeys();
        $targetForeignKeys = $targetTable->getForeignKeys();

        $foreignKeysToDrop = array_diff(array_keys($sourceForeignKeys), array_keys($targetForeignKeys));
        $foreignKeysToAdd = array_diff_key($targetForeignKeys, $sourceForeignKeys);
        $foreignKeysToChange = [];

        if (count($primaryColumnsToDrop) + count($primaryColumnsToAdd) +
            count($columnsToDrop) + count($columnsToAdd) + count($columnsToChange) +
            count($indexesToDrop) + count($indexesToAdd) + count($indexesToChange) +
            count($foreignKeysToDrop) + count($foreignKeysToAdd) + count($foreignKeysToChange)
            === 0
        ) {
            return null;
        }

        $migrationTable = new MigrationTable($sourceTable->getName());
        if (!empty($primaryColumnsToDrop)) {
            $migrationTable->dropPrimaryKey();
        }
        $primaryColumns = [];
        foreach ($primaryColumnsToAdd as $primaryColumnToAdd) {
            $primaryColumns[] = $targetTable->getColumn($primaryColumnToAdd);
        }
        if (!empty($primaryColumns)) {
            $migrationTable->addPrimaryColumns($primaryColumns);
        }

        foreach ($columnsToDrop as $columnToDrop) {
            if (in_array($columnToDrop, $primaryColumnsToDrop, true)) {
                continue;
            }
            $migrationTable->dropColumn($columnToDrop);
        }

        /** @var Column $columnToAdd */
        foreach ($columnsToAdd as $columnToAdd) {
            if (in_array($columnToAdd->getName(), $primaryColumnsToAdd, true)) {
                continue;
            }
            $migrationTable->addColumn($columnToAdd->getName(), $columnToAdd->getType(), $columnToAdd->getSettings()->getSettings());
        }

        /** @var Column $columnToChange */
        foreach ($columnsToChange as $columnToChangeName => $columnToChange) {
            $migrationTable->changeColumn($columnToChangeName, $columnToChange->getName(), $columnToChange->getType(), $columnToChange->getSettings()->getSettings());
        }

        foreach ($indexesToDrop as $indexToDrop) {
            $migrationTable->dropIndexByName($indexToDrop);
        }

        /** @var Index $indexToAdd */
        foreach ($indexesToAdd as $indexToAdd) {
            $migrationTable->addIndex($indexToAdd->getColumns(), $indexToAdd->getType(), $indexToAdd->getMethod(), $indexToAdd->getName());
        }

        foreach ($foreignKeysToDrop as $foreignKeyToDrop) {
            $migrationTable->dropForeignKey($foreignKeyToDrop);
        }

        /** @var ForeignKey $foreignKeyToAdd */
        foreach ($foreignKeysToAdd as $foreignKeyToAdd) {
            $migrationTable->addForeignKey($foreignKeyToAdd->getColumns(), $foreignKeyToAdd->getReferencedTable(), $foreignKeyToAdd->getReferencedColumns(), $foreignKeyToAdd->getOnDelete(), $foreignKeyToAdd->getOnUpdate());
        }

        return $migrationTable;
    }
}
