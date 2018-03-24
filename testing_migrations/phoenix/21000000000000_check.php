<?php

namespace Phoenix\TestingMigrations;

use Exception;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Table;
use Phoenix\Migration\AbstractMigration;

class Check extends AbstractMigration
{
    public function up(): void
    {
        $logs = $this->fetchAll('phoenix_log');
        if (count($logs) != 10) {
            throw new Exception('Wrong count');
        }

        $res = $this->select('SELECT COUNT(*) AS log_count FROM phoenix_log');
        if (count($logs) != $res[0]['log_count']) {
            throw new Exception('Counts don\'t match');
        }

        $tableColumns = [
            'renamed_table_1' => [
                'id', 'title', 'alias', 'is_active', 'bodytext', 'self_fk',
            ],
            'table_2' => [
                'id', 'title', 'new_sorting', 't1_fk', 'created_at',
            ],
            'table_3' => [
                'identifier', 't1_fk', 't2_fk', 'id',
            ],
            'table_4' => [
                'title', 'identifier',
            ],
            'new_table_2' => [
                'id', 'title', 'new_sorting', 't1_fk', 'created_at',
            ],
            'new_table_3' => [
                'identifier', 't1_fk', 't2_fk', 'id',
            ],
        ];

        foreach ($tableColumns as $table => $columns) {
            $firstItem = $this->fetch($table);
            if (count($firstItem) != count($columns)) {
                throw new Exception('Wrong number of columns in first item of table ' . $table);
            }
            $items = $this->fetchAll($table);
            if (!$items) {
                throw new Exception('No data in table "' . $table . '"');
            }
            foreach ($items as $item) {
                if (count($item) != count($columns)) {
                    throw new Exception('Wrong number of columns in item');
                }
                foreach ($columns as $column) {
                    if (!array_key_exists($column, $item)) {
                        throw new Exception('Column "' . $column . '" is not defined in item');
                    }
                }
            }
        }

        $table4Count = $this->fetch('table_4', ['count(*) as cnt']);
        if (intval($table4Count['cnt']) !== 10000) {
            throw new Exception('Items count in table_4 is not 3, but ' . $table4Count['cnt']);
        }

        $item1 = $this->fetch('renamed_table_1', ['*'], ['id' => 1]);
        if ((bool)$item1['is_active'] !== false) {
            throw new Exception('is_active for item with id 1 should be false');
        }

        $item2 = $this->fetch('renamed_table_1', ['*'], ['id' => 2]);
        if ((bool)$item2['is_active'] !== true) {
            throw new Exception('is_active for item with id 2 should be true');
        }

        $item3 = $this->fetch('renamed_table_1', ['*'], ['id' => 3]);
        if ((bool)$item3['is_active'] !== true) {
            throw new Exception('is_active for item with id 3 should be false');
        }

        if ($this->tableExists('non_existing_table')) {
            throw new Exception('non_existing_table exists!');
        }

        if ($this->tableColumnExists('non_existing_table', 'some_column')) {
            throw new Exception('non_existing_table.some_column exists!');
        }

        if ($this->tableColumnExists('table_2', 'non_existing_column')) {
            throw new Exception('table_2.non_existing_column exists!');
        }

        if ($this->getTable('non_existing_table') !== null) {
            throw new Exception('non_existing_table is not null');
        }

        if (!($this->getTable('table_2') instanceof Table)) {
            throw new Exception('table_2 is not a Table');
        }

        if (!($this->getTable('table_2')->getColumn('title') instanceof Column)) {
            throw new Exception('table_2.title is not a Column');
        }
    }

    public function down(): void
    {
    }
}
