<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\IndexColumnSettings;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;

final class MysqlAdapter extends PdoAdapter
{
    private ?MysqlQueryBuilder $queryBuilder = null;

    public function getQueryBuilder(): MysqlQueryBuilder
    {
        if (!$this->queryBuilder) {
            $features = [];
            if ($this->version && version_compare($this->version, '5.7.8', '>=')) {
                $features[] = MysqlQueryBuilder::FEATURE_JSON;
            }
            $this->queryBuilder = new MysqlQueryBuilder($this, $features);
        }
        return $this->queryBuilder;
    }

    public function buildDoNotCheckForeignKeysQuery(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 0;';
    }

    public function buildCheckForeignKeysQuery(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 1;';
    }

    protected function loadDatabase(): string
    {
        /** @var string $currentDatabase */
        $currentDatabase = $this->query('SELECT database()')->fetchColumn();
        return $currentDatabase;
    }

    protected function loadTables(string $database): array
    {
        /** @var array<string[]> $tables */
        $tables = $this->query(sprintf("SELECT TABLE_NAME AS table_name, TABLE_COLLATION AS table_collation, TABLE_COMMENT as table_comment, AUTO_INCREMENT as auto_increment FROM information_schema.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = '%s' ORDER BY TABLE_NAME", $database))->fetchAll(PDO::FETCH_ASSOC);
        return $tables;
    }

    protected function createMigrationTable(array $table): MigrationTable
    {
        $migrationTable = parent::createMigrationTable($table);
        if ($table['table_collation']) {
            list($charset,) = explode('_', $table['table_collation'], 2);
            $migrationTable->setCharset($charset);
            $migrationTable->setCollation($table['table_collation']);
        }
        if ($table['table_comment']) {
            $migrationTable->setComment($table['table_comment']);
        }
        if ($table['auto_increment']) {
            $migrationTable->setAutoIncrement((int)$table['auto_increment']);
        }
        return $migrationTable;
    }

    private function remapType(string $type): string
    {
        $types = [
            'int' => Column::TYPE_INTEGER,
            'bit' => Column::TYPE_BIT,
            'tinyint' => Column::TYPE_TINY_INTEGER,
            'smallint' => Column::TYPE_SMALL_INTEGER,
            'mediumint' => Column::TYPE_MEDIUM_INTEGER,
            'bigint' => Column::TYPE_BIG_INTEGER,
            'varchar' => Column::TYPE_STRING,
            'linestring' => Column::TYPE_LINE,
        ];
        return $types[$type] ?? $type;
    }

    protected function loadColumns(string $database): array
    {
        /** @var array<mixed[]> $columns */
        $columns = $this->query(sprintf("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '%s' ORDER BY TABLE_NAME, ORDINAL_POSITION", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tablesColumns = [];
        foreach ($columns as $column) {
            /** @var string $tableName */
            $tableName = $column['TABLE_NAME'];
            $tablesColumns[$tableName][] = $column;
        }
        return $tablesColumns;
    }

    protected function addColumn(MigrationTable $migrationTable, array $column): void
    {
        $type = $this->remapType($column['DATA_TYPE']);
        $settings = $this->prepareSettings($column);
        if ($type === Column::TYPE_CHAR && $settings[ColumnSettings::SETTING_LENGTH] === 36) {
            $type = Column::TYPE_UUID;
            unset($settings[ColumnSettings::SETTING_LENGTH]);
        } elseif ($type === Column::TYPE_TINY_INTEGER && $settings[ColumnSettings::SETTING_LENGTH] === 1 && in_array($settings[ColumnSettings::SETTING_DEFAULT], ['0', '1'], true)) {
            $type = Column::TYPE_BOOLEAN;
            $settings[ColumnSettings::SETTING_DEFAULT] = (bool)$settings[ColumnSettings::SETTING_DEFAULT];
            unset($settings[ColumnSettings::SETTING_LENGTH]);
            unset($settings[ColumnSettings::SETTING_SIGNED]);
        } elseif ($type === Column::TYPE_YEAR) {
            unset($settings[ColumnSettings::SETTING_LENGTH]);
        }
        $migrationTable->addColumn($column['COLUMN_NAME'], $type, $settings);
    }

    /**
     * @param array<string, mixed> $column
     * @return array<string, mixed>
     */
    private function prepareSettings(array $column): array
    {
        preg_match('/(.*?)\((.*?)\)(.*)/', $column['COLUMN_TYPE'], $matches);
        $values = null;
        if ($column['DATA_TYPE'] === Column::TYPE_ENUM || $column['DATA_TYPE'] === Column::TYPE_SET) {
            $values = explode('\',\'', substr($matches[2], 1, -1));
        }
        list($length, $decimals) = $this->getLengthAndDecimals($matches[2] ?? null);

        // Mysql 8 ignores length for integers
        if ($length === null) {
            if ($column['DATA_TYPE'] === 'int') {
                $length = 11;
            } elseif ($column['DATA_TYPE'] === 'bigint') {
                $length = 20;
            } elseif ($column['DATA_TYPE'] === 'tinyint') {
                $length = 4;
            } elseif ($column['DATA_TYPE'] === 'smallint') {
                $length = 6;
            } elseif ($column['DATA_TYPE'] === 'mediumint') {
                $length = 9;
            }
        }

        return [
            ColumnSettings::SETTING_AUTOINCREMENT => $column['EXTRA'] === 'auto_increment',
            ColumnSettings::SETTING_NULL => $column['IS_NULLABLE'] === 'YES',
            ColumnSettings::SETTING_DEFAULT => $column['COLUMN_DEFAULT'],
            ColumnSettings::SETTING_LENGTH => $length,
            ColumnSettings::SETTING_DECIMALS => $decimals,
            ColumnSettings::SETTING_SIGNED => !(isset($matches[3]) && trim($matches[3]) === 'unsigned'),
            ColumnSettings::SETTING_CHARSET => $column['CHARACTER_SET_NAME'],
            ColumnSettings::SETTING_COLLATION => $column['COLLATION_NAME'],
            ColumnSettings::SETTING_VALUES => $values,
            ColumnSettings::SETTING_COMMENT => $column['COLUMN_COMMENT'] ?: null,
        ];
    }

    protected function loadIndexes(string $database): array
    {
        /** @var array<mixed[]> $indexes */
        $indexes = $this->query(sprintf("SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '%s'", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tablesIndexes = [];
        foreach ($indexes as $index) {
            /** @var string $tableName */
            $tableName = $index['TABLE_NAME'];
            /** @var string $indexName */
            $indexName = $index['INDEX_NAME'];
            if (!isset($tablesIndexes[$tableName])) {
                $tablesIndexes[$tableName] = [];
            }

            $indexColumnSettings = [];
            if ($index['SUB_PART']) {
                $indexColumnSettings[IndexColumnSettings::SETTING_LENGTH] = (int) $index['SUB_PART'];
            }
            if ($index['COLLATION'] === 'D') {
                $indexColumnSettings[IndexColumnSettings::SETTING_ORDER] = IndexColumnSettings::SETTING_ORDER_DESC;
            }

            $tablesIndexes[$tableName][$indexName]['columns'][$index['SEQ_IN_INDEX']] = new IndexColumn($index['COLUMN_NAME'], $indexColumnSettings);
            $tablesIndexes[$tableName][$indexName]['type'] = in_array($index['NON_UNIQUE'], [0, '0'], true) ? Index::TYPE_UNIQUE : ($index['INDEX_TYPE'] === 'FULLTEXT' ? Index::TYPE_FULLTEXT : Index::TYPE_NORMAL);
            $tablesIndexes[$tableName][$indexName]['method'] = $index['INDEX_TYPE'] === 'FULLTEXT' ? Index::METHOD_DEFAULT : $index['INDEX_TYPE'];
        }
        return $tablesIndexes;
    }

    protected function loadForeignKeys(string $database): array
    {
        $query = sprintf('SELECT * FROM information_schema.KEY_COLUMN_USAGE
INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS ON information_schema.KEY_COLUMN_USAGE.CONSTRAINT_NAME = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
AND information_schema.KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA
WHERE information_schema.KEY_COLUMN_USAGE.TABLE_SCHEMA = "%s";', $database);
        /** @var array<mixed[]> $foreignKeyColumns */
        $foreignKeyColumns = $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            /** @var string $tableName */
            $tableName = $foreignKeyColumn['TABLE_NAME'];
            /** @var string $constraintName */
            $constraintName = $foreignKeyColumn['CONSTRAINT_NAME'];
            $foreignKeys[$tableName][$constraintName]['columns'][] = $foreignKeyColumn['COLUMN_NAME'];
            $foreignKeys[$tableName][$constraintName]['referenced_table'] = $foreignKeyColumn['REFERENCED_TABLE_NAME'];
            $foreignKeys[$tableName][$constraintName]['referenced_columns'][] = $foreignKeyColumn['REFERENCED_COLUMN_NAME'];
            $foreignKeys[$tableName][$constraintName]['on_update'] = $foreignKeyColumn['UPDATE_RULE'];
            $foreignKeys[$tableName][$constraintName]['on_delete'] = $foreignKeyColumn['DELETE_RULE'];
        }
        return $foreignKeys;
    }

    protected function loadUniqueConstraints(string $database): array
    {
        return [];
    }

    protected function escapeString(string $string): string
    {
        return '`' . $string . '`';
    }

    protected function createRealValue($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        } elseif (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return $value;
    }
}
