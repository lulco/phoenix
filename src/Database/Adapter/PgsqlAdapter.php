<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\IndexColumnSettings;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\Exception\DatabaseQueryExecuteException;

final class PgsqlAdapter extends PdoAdapter
{
    private ?PgsqlQueryBuilder $queryBuilder = null;

    public function getQueryBuilder(): PgsqlQueryBuilder
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new PgsqlQueryBuilder($this);
        }
        return $this->queryBuilder;
    }

    public function buildDoNotCheckForeignKeysQuery(): string
    {
        return 'SET session_replication_role = replica;';
    }

    public function buildCheckForeignKeysQuery(): string
    {
        return 'SET session_replication_role = DEFAULT;';
    }

    protected function loadDatabase(): string
    {
        /** @var string $currentDatabase */
        $currentDatabase = $this->query('SELECT current_database()')->fetchColumn();
        return $currentDatabase;
    }

    protected function loadTables(string $database): array
    {
        /** @var array<mixed[]> $tables */
        $tables = $this->query(sprintf("
            SELECT *
            FROM INFORMATION_SCHEMA.TABLES
            WHERE table_type = 'BASE TABLE' AND table_catalog = '%s' AND table_schema='public'
            ORDER BY TABLE_NAME", $database))->fetchAll(PDO::FETCH_ASSOC);
        return $tables;
    }

    protected function createMigrationTable(array $table): MigrationTable
    {
        $migrationTable = parent::createMigrationTable($table);
        /** @var string|false $comment */
        $comment = $this->query(sprintf("
            SELECT description
            FROM pg_description
            JOIN pg_class ON pg_description.objoid = pg_class.oid
            WHERE relname = '%s'", $table['table_name']))->fetchColumn();
        if ($comment) {
            $migrationTable->setComment($comment);
        }
        return $migrationTable;
    }

    protected function loadColumns(string $database): array
    {
        /** @var array<mixed[]> $columns */
        $columns = $this->query(sprintf("
            SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_catalog = '%s' AND table_schema = 'public'
            ORDER BY table_name, ordinal_position", $database))->fetchAll(PDO::FETCH_ASSOC);

        /** @var array<string[]> $comments */
        $comments = $this->query(sprintf("
            SELECT c.table_name,c.column_name,pgd.description
            FROM pg_catalog.pg_statio_all_tables AS st
            INNER JOIN pg_catalog.pg_description pgd ON pgd.objoid = st.relid
            INNER JOIN information_schema.columns c ON pgd.objsubid = c.ordinal_position AND c.table_schema = st.schemaname AND c.table_name = st.relname
            WHERE c.table_schema = 'public' AND c.table_catalog = '%s'", $database))->fetchAll(PDO::FETCH_ASSOC);

        $tableColumnComments = [];
        foreach ($comments as $tableColumnComment) {
            $tableColumnComments[$tableColumnComment['table_name']][$tableColumnComment['column_name']] = $tableColumnComment['description'];
        }

        $tablesColumns = [];
        foreach ($columns as $column) {
            /** @var string $tableName */
            $tableName = $column['table_name'];
            $column['comment'] = $tableColumnComments[$tableName][$column['column_name']] ?? null;
            $tablesColumns[$tableName][] = $column;
        }
        return $tablesColumns;
    }

    protected function addColumn(MigrationTable $migrationTable, array $column): void
    {
        $type = $this->remapType($column['data_type']);
        $settings = $this->prepareSettings($type, $column, $migrationTable->getName());
        $migrationTable->addColumn($column['column_name'], $type, $settings);
    }

    public function getSequenceName(MigrationTable $migrationTable): ?string
    {
        $query = sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_catalog = '%s' AND table_schema = 'public' AND table_name = '%s' AND column_default LIKE 'nextval%%'", $this->loadDatabase(), $migrationTable->getName());
        $autoIncrementColumn = $this->query($query)->fetch(PDO::FETCH_ASSOC);
        if (!$autoIncrementColumn) {
            return null;
        }
        preg_match('/nextval\(\'(.*?)\'/', $autoIncrementColumn['column_default'], $matches);
        return $matches[1] ?? null;
    }

    private function remapType(string $type): string
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
            'timestamp with time zone' => Column::TYPE_TIMESTAMP_TZ,
            'USER-DEFINED' => Column::TYPE_ENUM,
            'ARRAY' => Column::TYPE_SET,
            'time without time zone' => Column::TYPE_TIME,
        ];
        return $types[$type] ?? $type;
    }

    /**
     * @param array<string, mixed> $column
     * @return array<string, mixed>
     * @throws DatabaseQueryExecuteException
     */
    private function prepareSettings(string $type, array $column, string $table): array
    {
        $length = null;
        $decimals = null;
        if (in_array($type, [Column::TYPE_STRING, Column::TYPE_CHAR, Column::TYPE_BIT], true)) {
            $length = $column['character_maximum_length'];
        } elseif (in_array($type, [Column::TYPE_NUMERIC], true)) {
            $length = $column['numeric_precision'];
            $decimals = $column['numeric_scale'];
        }

        $settings = [
            ColumnSettings::SETTING_NULL => $column['is_nullable'] === 'YES',
            ColumnSettings::SETTING_DEFAULT => $this->prepareDefault($column, $type),
            ColumnSettings::SETTING_LENGTH => $length,
            ColumnSettings::SETTING_DECIMALS => $decimals,
            ColumnSettings::SETTING_AUTOINCREMENT => is_string($column['column_default']) && strpos($column['column_default'], 'nextval') === 0,
            ColumnSettings::SETTING_COMMENT => $column['comment'],
        ];
        if (in_array($type, [Column::TYPE_ENUM, Column::TYPE_SET], true)) {
            $enumType = $table . '__' . $column['column_name'];
            $settings[ColumnSettings::SETTING_VALUES] = $this->query("SELECT unnest(enum_range(NULL::$enumType))")->fetchAll(PDO::FETCH_COLUMN);
        }
        return $settings;
    }

    /**
     * @param array<string, mixed> $column
     * @return mixed
     */
    private function prepareDefault(array $column, string $type)
    {
        if (!$column['column_default']) {
            return null;
        }
        $default = $column['column_default'];
        if ($type === Column::TYPE_BOOLEAN) {
            $default = $default === 'true';
        } elseif (substr($default, 0, 6) === 'NULL::' || substr($default, 0, 7) === 'nextval') {
            $default = null;
        }
        return $default;
    }

    protected function loadIndexes(string $database): array
    {
        // there are too many indexes which are not required - try to select only those from actual database
        $query = 'SELECT a.index_name, b.attname, a.relname, a.indisunique, a.indisprimary, a.indoption FROM (
            SELECT a.indrelid, a.indisunique, a.indoption, b.relname, a.indisprimary, c.relname index_name, unnest(a.indkey) index_num
            FROM pg_index a, pg_class b, pg_class c
            WHERE b.oid=a.indrelid AND a.indexrelid=c.oid
            ) a, pg_attribute b WHERE a.indrelid = b.attrelid AND a.index_num = b.attnum ORDER BY a.index_name, a.index_num';
        /** @var array<mixed[]> $indexRows */
        $indexRows = $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $indexes = [];
        foreach ($indexRows as $indexRow) {
            /** @var string $relname */
            $relname = $indexRow['relname'];
            /** @var string $indexName */
            $indexName = $indexRow['index_name'];
            if ($indexRow['indisprimary']) {
                $indexes[$relname]['PRIMARY']['columns'][] = new IndexColumn($indexRow['attname']);
                continue;
            }

            $settings = [];

            $position = count($indexes[$relname][$indexName]['columns'] ?? []);
            $indoptions = explode(' ', $indexRow['indoption']);
            $indoption = (int)($indoptions[$position] ?? 0);

            if ($indoption & 1) {
                $settings[IndexColumnSettings::SETTING_ORDER] = IndexColumnSettings::SETTING_ORDER_DESC;
            }

            if ($indoption & 2) {
                // ready for NULLS FIRST
            }

            $indexes[$relname][$indexName]['columns'][] = new IndexColumn($indexRow['attname'], $settings);
            $indexes[$relname][$indexName]['type'] = $indexRow['indisunique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
            $indexes[$relname][$indexName]['method'] = Index::METHOD_DEFAULT;
        }

        /** @var array<mixed[]> $substringIndexRows */
        $substringIndexRows = $this->query("SELECT pg_index.indisunique, pg_index.indoption, index_info.relname AS index_name, table_info.relname AS table_name, pg_index.indexprs
FROM pg_index
INNER JOIN pg_class AS index_info ON index_info.relfilenode = pg_index.indexrelid
INNER JOIN pg_class AS table_info ON table_info.relfilenode = pg_index.indrelid
INNER JOIN pg_attribute ON pg_index.indexrelid = pg_attribute.attrelid
WHERE pg_attribute.attname = 'substring'")->fetchAll(PDO::FETCH_ASSOC);

        /** @var array<mixed[]> $columns */
        $columns = $this->query(sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_catalog = '%s' AND table_schema = 'public'", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tableColumns = [];
        foreach ($columns as $column) {
            $tableColumns[$column['table_name']][$column['ordinal_position']] = $column;
        }
        foreach ($substringIndexRows as $substringIndexRow) {
            /** @var string $tableName */
            $tableName = $substringIndexRow['table_name'];
            preg_match_all('/varattno ([0-9]+) (.*?):constvalue 4 \[ ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) \]}\) :location/', $substringIndexRow['indexprs'], $matches);

            $indoptions = explode(' ', $substringIndexRow['indoption']);

            $indexColumns = [];
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $ordinalPosition = $matches[1][$i];
                $length = $matches[3][$i];
                $indexColumnSettings = [
                    IndexColumnSettings::SETTING_LENGTH => (int)$length,
                ];
                $indoption = (int)($indoptions[$i] ?? 0);
                if ($indoption & 1) {
                    $indexColumnSettings[IndexColumnSettings::SETTING_ORDER] = IndexColumnSettings::SETTING_ORDER_DESC;
                }
                if ($indoption & 2) {
                    // ready for NULLS FIRST
                }
                $indexColumns[] = new IndexColumn($tableColumns[$tableName][$ordinalPosition]['column_name'], $indexColumnSettings);
            }

            $indexes[$tableName][(string)$substringIndexRow['index_name']] = [
                'columns' => $indexColumns,
                'type' => $substringIndexRow['indisunique'] ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL,
                'method' => Index::METHOD_DEFAULT,
            ];
        }

        return $indexes;
    }

    protected function loadForeignKeys(string $database): array
    {
        $query = "SELECT tc.constraint_name, tc.table_name, kcu.column_name, kcu.ordinal_position, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name, pgc.confupdtype, pgc.confdeltype
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
            JOIN pg_constraint AS pgc ON pgc.conname = tc.constraint_name
            WHERE constraint_type = 'FOREIGN KEY'";

        /** @var array<mixed[]> $foreignKeyColumns */
        $foreignKeyColumns = $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            /** @var string $tableName */
            $tableName = $foreignKeyColumn['table_name'];
            /** @var string $constraintName */
            $constraintName = $foreignKeyColumn['constraint_name'];
            $foreignKeys[$tableName][$constraintName]['columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['column_name'];
            $foreignKeys[$tableName][$constraintName]['referenced_table'] = $foreignKeyColumn['foreign_table_name'];
            $foreignKeys[$tableName][$constraintName]['referenced_columns'][$foreignKeyColumn['ordinal_position']] = $foreignKeyColumn['foreign_column_name'];
            $foreignKeys[$tableName][$constraintName]['on_delete'] = $this->remapForeignKeyAction($foreignKeyColumn['confdeltype']);
            $foreignKeys[$tableName][$constraintName]['on_update'] = $this->remapForeignKeyAction($foreignKeyColumn['confupdtype']);
        }
        return $foreignKeys;
    }

    protected function loadUniqueConstraints(string $database): array
    {
        $query = "SELECT tc.constraint_name, tc.table_name, kcu.column_name
                  FROM information_schema.table_constraints AS tc
                  JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
                  WHERE tc.constraint_type = 'UNIQUE'
                  AND tc.constraint_schema = 'public'";

        /** @var array<mixed[]> $uniqueConstraintKeys */
        $uniqueConstraintKeys = $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $uniqueConstraints = [];
        foreach ($uniqueConstraintKeys as $uniqueConstraintKey) {
            /** @var string $tableName */
            $tableName = $uniqueConstraintKey['table_name'];
            /** @var string $constraintName */
            $constraintName = $uniqueConstraintKey['constraint_name'];
            $uniqueConstraints[$tableName][$constraintName]['columns'][] = $uniqueConstraintKey['column_name'];
        }

        return $uniqueConstraints;
    }

    private function remapForeignKeyAction(string $action): string
    {
        $actionMap = [
            'a' => ForeignKey::NO_ACTION,
            'c' => ForeignKey::CASCADE,
            'n' => ForeignKey::SET_NULL,
            'r' => ForeignKey::RESTRICT,
        ];
        return $actionMap[$action] ?? $action;
    }

    protected function escapeString(string $string): string
    {
        return '"' . $string . '"';
    }

    /**
     * {@inheritdoc}
     */
    protected function createRealValue($value)
    {
        if ($value === false) {
            return 'false';
        } elseif ($value === true) {
            return 'true';
        }
        return is_array($value) ? '{' . implode(',', $value) . '}' : $value;
    }
}
