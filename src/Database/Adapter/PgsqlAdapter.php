<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;

class PgsqlAdapter extends PdoAdapter
{
    /**
     * @return PgsqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new PgsqlQueryBuilder($this->getStructure());
        }
        return $this->queryBuilder;
    }

    protected function loadStructure()
    {
        $database = $this->execute('SELECT current_database()')->fetchColumn();
        $structure = new Structure();
        $tables = $this->execute("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_catalog = '$database' AND table_schema='public' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->tableInfo($table['table_name']);
            if ($migrationTable) {
                $structure->update($migrationTable);
            }
        }
        return $structure;
    }

    private function tableInfo($table)
    {
        $columns = $this->execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table'")->fetchAll(PDO::FETCH_ASSOC);
        $migrationTable = new MigrationTable($table);
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
            $migrationTable->addColumn($column['column_name'], $type, $settings);
        }

        // http://www.alberton.info/postgresql_meta_info.html#.WMuSe31tnIU
        $indexRows = $this->execute("SELECT a.index_name, b.attname, a.indisunique
  FROM (
    SELECT a.indrelid,
		   a.indisunique,
           c.relname index_name,
           unnest(a.indkey) index_num
      FROM pg_index a,
           pg_class b,
           pg_class c
     WHERE b.relname='$table'
       AND b.oid=a.indrelid
       AND a.indisprimary != 't'
       AND a.indexrelid=c.oid
       ) a,
       pg_attribute b
 WHERE a.indrelid = b.attrelid
   AND a.index_num = b.attnum
 ORDER BY a.index_name, a.index_num");
        $indexes = [];
        foreach ($indexRows as $indexRow) {
            $indexes[$indexRow['index_name']]['columns'][] = $indexRow['attname'];
            $indexes[$indexRow['index_name']]['type'] = $indexRow['indisunique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
        }

        foreach ($indexes as $name => $index) {
            $migrationTable->addIndex($index['columns'], $index['type'], Index::METHOD_DEFAULT, $name);
        }

        // TODO foreign keys

        return $migrationTable;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? '{' . implode(',', $value) . '}' : $value;
    }

    protected function escapeString($string)
    {
        return '"' . $string . '"';
    }
}
