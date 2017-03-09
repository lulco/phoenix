<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;

class PgsqlAdapter extends PdoAdapter
{
    /**
     * @return PgsqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new PgsqlQueryBuilder($this);
        }
        return $this->queryBuilder;
    }

    protected function loadStructure()
    {
        return new \Phoenix\Database\Element\Structure();
    }
    
    public function tableInfo($table)
    {
        $columns = $this->execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table'")->fetchAll(PDO::FETCH_ASSOC);
        $tableInfo = [];
        foreach ($columns as $column) {
            $type = $column['data_type'];
            $settings = [
                'null' => $column['is_nullable'] == 'YES',
                'default' => $column['column_default'],
                'length' => $column['character_maximum_length'],
                'autoincrement' => strpos($column['column_default'], 'nextval') === 0,
            ];
            if (in_array($column['data_type'], ['USER-DEFINED', 'ARRAY'])) {
                if ($column['data_type'] == 'USER-DEFINED') {
                    $type = Column::TYPE_ENUM;
                } else {
                    $type = Column::TYPE_SET;
                }
                $enumType = $table . '__' . $column['column_name'];
                $settings['values'] = $this->execute("SELECT unnest(enum_range(NULL::$enumType))")->fetchAll(PDO::FETCH_COLUMN);
            }
            $tableInfo[$column['column_name']] = new Column($column['column_name'], $type, $settings);
        }
        return $tableInfo;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? '{' . implode(',', $value) . '}' : $value;
    }
}
