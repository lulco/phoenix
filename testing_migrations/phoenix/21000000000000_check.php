<?php

namespace Phoenix\TestingMigrations;

use Exception;
use Phoenix\Migration\AbstractMigration;

class Check extends AbstractMigration
{
    public function up(): void
    {
        $logs = $this->fetchAll('phoenix_log');
        if (count($logs) != 7) {
            throw new Exception('Wrong count');
        }

        $res = $this->select('SELECT COUNT(*) AS log_count FROM phoenix_log');
        if (count($logs) != $res[0]['log_count']) {
            throw new Exception('Counts don\'t match');
        }

        $tableColumns = [
            'renamed_table_1' => [
                'id', 'title', 'alias', 'is_active', 'bodytext', 'self_fk'
            ],
            'table_2' => [
                'id', 'title', 'new_sorting', 't1_fk', 'created_at'
            ],
            'table_3' => [
                'identifier', 't1_fk', 't2_fk',
            ],
            'new_table_2' => [
                'id', 'title', 'new_sorting', 't1_fk', 'created_at'
            ],
            'new_table_3' => [
                'identifier', 't1_fk', 't2_fk',
            ],
        ];

        foreach ($tableColumns as $table => $columns) {
            $firstItem = $this->fetch($table);
            if (count($firstItem) != count($columns)) {
                throw new Exception('Wrong number of columns in first item');
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
    }

    public function down(): void
    {
    }
}
