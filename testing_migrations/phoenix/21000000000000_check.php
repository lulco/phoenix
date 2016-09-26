<?php

namespace Phoenix\TestingMigrations;

use Exception;
use Phoenix\Migration\AbstractMigration;

class Check extends AbstractMigration
{
    public function up()
    {
        $logs = $this->fetchAll('phoenix_log');
        if (count($logs) != 4) {
            throw new Exception('Wrong count');
        }

        $tableColumns = [
            'table_1' => [
                'id', 'title', 'alias', 'is_active', 'bodytext'
            ],
            'table_2' => [
                'id', 'title', 'new_sorting', 't1_fk', 'created_at'
            ],
            'table_3' => [
                'identifier', 't1_fk', 't2_fk',
            ],
        ];

        foreach ($tableColumns as $table => $columns) {
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

    public function down()
    {
    }
}
