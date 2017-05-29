<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
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
            $this->queryBuilder = new PgsqlQueryBuilder($this);
        }
        return $this->queryBuilder;
    }

    protected function loadStructure()
    {
        $database = $this->execute('SELECT current_database()')->fetchColumn();
        $structure = new Structure();
        $tables = $this->execute("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_catalog = '$database' AND table_schema='public' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->createMigrationTable($table['table_name']);
            $structure->update($migrationTable);
        }
        return $structure;
    }

    private function createMigrationTable($table)
    {
        $migrationTable = new MigrationTable($table, false);
        $this->loadColumns($migrationTable, $table);
        $this->loadIndexes($migrationTable, $table);
        $this->loadForeignKeys($migrationTable, $table);
        $migrationTable->create();
        return $migrationTable;
    }

    private function loadColumns(MigrationTable $migrationTable, $table)
    {
        $columns = $this->execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $type = $this->remapType($column['data_type']);
            $settings = $this->prepareSettings($type, $column, $table);
            $migrationTable->addColumn($column['column_name'], $type, $settings);
        }
    }

    private function remapType($type)
    {
        $types = [
            'smallint' => Column::TYPE_SMALL_INTEGER,
            'bigint' => Column::TYPE_BIG_INTEGER,
            'real' => Column::TYPE_FLOAT,
            'float4' => Column::TYPE_FLOAT,
            'double precision' => Column::TYPE_DOUBLE,
            'float8' => Column::TYPE_DOUBLE,
            'varchar' => Column::TYPE_STRING,
            'character' => Column::TYPE_CHAR,
            'character varying' => Column::TYPE_STRING,
            'bytea' => Column::TYPE_BLOB,
            'timestamp without time zone' => Column::TYPE_DATETIME,
            'USER-DEFINED' => Column::TYPE_ENUM,
            'ARRAY' => Column::TYPE_SET,
        ];
        return isset($types[$type]) ? $types[$type] : $type;
    }

    private function prepareSettings($type, $column, $table)
    {
        $length = null;
        $decimals = null;
        if (in_array($type, [Column::TYPE_STRING, Column::TYPE_CHAR])) {
            $length = $column['character_maximum_length'];
        } elseif (in_array($type, [Column::TYPE_NUMERIC])) {
            $length = $column['numeric_precision'];
            $decimals = $column['numeric_scale'];
        }

        $settings = [
            'null' => $column['is_nullable'] == 'YES',
            'default' => $this->prepareDefault($column, $type),
            'length' => $length,
            'decimals' => $decimals,
            'autoincrement' => strpos($column['column_default'], 'nextval') === 0,
        ];
        if (in_array($type, [Column::TYPE_ENUM, Column::TYPE_SET])) {
            $enumType = $table . '__' . $column['column_name'];
            $settings['values'] = $this->execute("SELECT unnest(enum_range(NULL::$enumType))")->fetchAll(PDO::FETCH_COLUMN);
        }
        return $settings;
    }

    private function prepareDefault($column, $type)
    {
        if (!$column['column_default']) {
            return null;
        }
        $default = $column['column_default'];
        if ($type === Column::TYPE_BOOLEAN) {
            $default = $default === 'true';
        } elseif (substr($default, 0, 6) == 'NULL::' || substr($default, 0, 7) == 'nextval') {
            $default = null;
        }
        return $default;
    }

    /**
     * http://www.alberton.info/postgresql_meta_info.html#.WMuSe31tnIU
     * @param MigrationTable $migrationTable
     * @param string $table
     */
    private function loadIndexes(MigrationTable $migrationTable, $table)
    {
        $indexRows = $this->execute("SELECT a.index_name, b.attname, a.indisunique, a.indisprimary
  FROM (
    SELECT a.indrelid,
		   a.indisunique,
           a.indisprimary,
           c.relname index_name,
           unnest(a.indkey) index_num
    FROM pg_index a, pg_class b, pg_class c
    WHERE b.relname='$table' AND b.oid=a.indrelid AND a.indexrelid=c.oid
       ) a, pg_attribute b
 WHERE a.indrelid = b.attrelid AND a.index_num = b.attnum
 ORDER BY a.index_name, a.index_num");
        $indexes = [];
        $primaryKeys = [];
        foreach ($indexRows as $indexRow) {
            if ($indexRow['indisprimary']) {
                $primaryKeys[] = $indexRow['attname'];
                continue;
            }
            $indexes[$indexRow['index_name']]['columns'][] = $indexRow['attname'];
            $indexes[$indexRow['index_name']]['type'] = $indexRow['indisunique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
        }

        foreach ($indexes as $name => $index) {
            $migrationTable->addIndex($index['columns'], $index['type'], Index::METHOD_DEFAULT, $name);
        }

        $migrationTable->addPrimary($primaryKeys);
    }

    /**
     * http://stackoverflow.com/questions/1152260/postgres-sql-to-list-table-foreign-keys
     * @param MigrationTable $migrationTable
     * @param string $table
     */
    private function loadForeignKeys(MigrationTable $migrationTable, $table)
    {
        $query = sprintf("SELECT tc.constraint_name, kcu.column_name, kcu.ordinal_position,
 ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name,
 pgc.confupdtype, pgc.confdeltype
 FROM information_schema.table_constraints AS tc
 JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
 JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
 JOIN pg_constraint AS pgc ON pgc.conname = tc.constraint_name
 WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name='%s';", $table);

        $foreignKeyColumns = $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            $foreignKeys[$foreignKeyColumn['constraint_name']]['columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['column_name'];
            $foreignKeys[$foreignKeyColumn['constraint_name']]['referenced_table'] = $foreignKeyColumn['foreign_table_name'];
            $foreignKeys[$foreignKeyColumn['constraint_name']]['referenced_columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['foreign_column_name'];
            $foreignKeys[$foreignKeyColumn['constraint_name']]['on_delete'] = $this->remapForeignKeyAction($foreignKeyColumn['confdeltype']);
            $foreignKeys[$foreignKeyColumn['constraint_name']]['on_update'] = $this->remapForeignKeyAction($foreignKeyColumn['confupdtype']);
        }

        foreach ($foreignKeys as $foreignKey) {
            ksort($foreignKey['columns']);
            ksort($foreignKey['referenced_columns']);
            $migrationTable->addForeignKey(array_values($foreignKey['columns']), $foreignKey['referenced_table'], array_values($foreignKey['referenced_columns']), $foreignKey['on_delete'], $foreignKey['on_update']);
        }
    }

    private function remapForeignKeyAction($action)
    {
        $actionMap = [
            'a' => ForeignKey::NO_ACTION,
            'c' => ForeignKey::CASCADE,
            'n' => ForeignKey::SET_NULL,
            'r' => ForeignKey::RESTRICT,
        ];
        return isset($actionMap[$action]) ? $actionMap[$action] : $action;
    }

    protected function escapeString($string)
    {
        return '"' . $string . '"';
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? '{' . implode(',', $value) . '}' : $value;
    }
}
