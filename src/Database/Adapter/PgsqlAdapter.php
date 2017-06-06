<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
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

    protected function loadDatabase()
    {
        return $this->execute('SELECT current_database()')->fetchColumn();
    }

    protected function loadTables($database)
    {
        return $this->execute(sprintf("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_catalog = '%s' AND table_schema='public' ORDER BY TABLE_NAME", $database))->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function loadColumns($database)
    {
        $columns = $this->execute(sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_catalog = '%s' AND table_schema = 'public' ORDER BY table_name, ordinal_position", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tablesColumns = [];
        foreach ($columns as $column) {
            $tablesColumns[$column['table_name']][] = $column;
        }
        return $tablesColumns;
    }

    protected function addColumn(MigrationTable $migrationTable, array $column)
    {
        $type = $this->remapType($column['data_type']);
        $settings = $this->prepareSettings($type, $column, $migrationTable->getName());
        $migrationTable->addColumn($column['column_name'], $type, $settings);
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
            ColumnSettings::SETTING_NULL => $column['is_nullable'] == 'YES',
            ColumnSettings::SETTING_DEFAULT => $this->prepareDefault($column, $type),
            ColumnSettings::SETTING_LENGTH => $length,
            ColumnSettings::SETTING_DECIMALS => $decimals,
            ColumnSettings::SETTING_AUTOINCREMENT => strpos($column['column_default'], 'nextval') === 0,
        ];
        if (in_array($type, [Column::TYPE_ENUM, Column::TYPE_SET])) {
            $enumType = $table . '__' . $column['column_name'];
            $settings[ColumnSettings::SETTING_VALUES] = $this->execute("SELECT unnest(enum_range(NULL::$enumType))")->fetchAll(PDO::FETCH_COLUMN);
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

    protected function loadIndexes($database)
    {
        $indexRows = $this->execute("SELECT a.index_name, b.attname, a.relname, a.indisunique, a.indisprimary FROM (
    SELECT a.indrelid, a.indisunique, b.relname, a.indisprimary, c.relname index_name, unnest(a.indkey) index_num
    FROM pg_index a, pg_class b, pg_class c
    WHERE b.oid=a.indrelid AND a.indexrelid=c.oid
    ) a, pg_attribute b WHERE a.indrelid = b.attrelid AND a.index_num = b.attnum ORDER BY a.index_name, a.index_num");
        $indexes = [];
        foreach ($indexRows as $indexRow) {
            if ($indexRow['indisprimary']) {
                $indexes[$indexRow['relname']]['PRIMARY']['columns'][] = $indexRow['attname'];
                continue;
            }
            $indexes[$indexRow['relname']][$indexRow['index_name']]['columns'][] = $indexRow['attname'];
            $indexes[$indexRow['relname']][$indexRow['index_name']]['type'] = $indexRow['indisunique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
            $indexes[$indexRow['relname']][$indexRow['index_name']]['method'] = Index::METHOD_DEFAULT;
        }
        return $indexes;
    }

    protected function loadForeignKeys($database)
    {
        $query = "SELECT tc.constraint_name, tc.table_name, kcu.column_name, kcu.ordinal_position, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name, pgc.confupdtype, pgc.confdeltype
    FROM information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
    JOIN pg_constraint AS pgc ON pgc.conname = tc.constraint_name
    WHERE constraint_type = 'FOREIGN KEY'";

        $foreignKeyColumns = $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            $foreignKeys[$foreignKeyColumn['table_name']][$foreignKeyColumn['constraint_name']]['columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['column_name'];
            $foreignKeys[$foreignKeyColumn['table_name']][$foreignKeyColumn['constraint_name']]['referenced_table'] = $foreignKeyColumn['foreign_table_name'];
            $foreignKeys[$foreignKeyColumn['table_name']][$foreignKeyColumn['constraint_name']]['referenced_columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['foreign_column_name'];
            $foreignKeys[$foreignKeyColumn['table_name']][$foreignKeyColumn['constraint_name']]['on_delete'] = $this->remapForeignKeyAction($foreignKeyColumn['confdeltype']);
            $foreignKeys[$foreignKeyColumn['table_name']][$foreignKeyColumn['constraint_name']]['on_update'] = $this->remapForeignKeyAction($foreignKeyColumn['confupdtype']);
        }
        return $foreignKeys;
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
