<?php

namespace Comparator;

use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;

class StructureComparator
{
    /**
     * @param Structure $sourceStructure
     * @param Structure $targetStructure
     * @return MigrationTable[]
     */
    public function diff(Structure $sourceStructure, Structure $targetStructure): array
    {
        $diff = [];

        $sourceTables = $sourceStructure->getTables();
        $targetTables = $targetStructure->getTables();

        $tablesToDrop = array_diff(array_keys($sourceTables), array_keys($targetTables));
        foreach ($tablesToDrop as $tableToDropName) {
            $migrationTable = new MigrationTable($tableToDropName);
            $migrationTable->drop();
            $diff[] = $migrationTable;
        }

        $tablesToAdd = array_diff_key($targetTables, $sourceTables);
        /** @var Table $tableToAdd */
        foreach ($tablesToAdd as $tableToAdd) {
            $migrationTable = $tableToAdd->toMigrationTable();
            $migrationTable->create();
            $diff[] = $migrationTable;
        }

        $tableComparator = new TableComparator();
        $intersect = array_intersect(array_keys($sourceTables), array_keys($targetTables));
        foreach ($intersect as $tableName) {
            $sourceTable = $sourceStructure->getTable($tableName);
            $targetTable = $targetStructure->getTable($tableName);
            $migrationTable = $tableComparator->diff($sourceTable, $targetTable);
            if ($migrationTable) {
                $diff[] = $migrationTable;
            }
        }

        return $diff;
    }
}
