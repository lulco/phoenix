<?php

declare(strict_types=1);

namespace Phoenix\Database\QueryBuilder;

use InvalidArgumentException;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\IndexColumnSettings;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Table;

final class PgsqlQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected function typeMap() : array
    {
        return [
            Column::TYPE_BIT => 'bit(%d)',
            Column::TYPE_TINY_INTEGER => 'int2',
            Column::TYPE_SMALL_INTEGER => 'int2',
            Column::TYPE_MEDIUM_INTEGER => 'int4',
            Column::TYPE_INTEGER => 'int4',
            Column::TYPE_BIG_INTEGER => 'int8',
            Column::TYPE_NUMERIC => 'numeric(%d,%d)',
            Column::TYPE_DECIMAL => 'numeric(%d,%d)',
            Column::TYPE_FLOAT => 'float4',
            Column::TYPE_DOUBLE => 'float8',
            Column::TYPE_BINARY => 'bytea',
            Column::TYPE_VARBINARY => 'bytea',
            Column::TYPE_CHAR => 'char(%d)',
            Column::TYPE_STRING => 'varchar(%d)',
            Column::TYPE_BOOLEAN => 'bool',
            Column::TYPE_DATE => 'date',
            Column::TYPE_DATETIME => 'timestamp(6)',
            Column::TYPE_TIMESTAMP => 'timestamp(6)',
            Column::TYPE_TIMESTAMP_TZ => 'timestamptz',
            Column::TYPE_YEAR => 'numeric(4)',
            Column::TYPE_TINY_TEXT => 'text',
            Column::TYPE_MEDIUM_TEXT => 'text',
            Column::TYPE_TEXT => 'text',
            Column::TYPE_LONG_TEXT => 'text',
            Column::TYPE_TINY_BLOB => 'bytea',
            Column::TYPE_MEDIUM_BLOB => 'bytea',
            Column::TYPE_BLOB => 'bytea',
            Column::TYPE_LONG_BLOB => 'bytea',
            Column::TYPE_UUID => 'uuid',
            Column::TYPE_JSON => 'json',
            Column::TYPE_ENUM => '%s__%s',
            Column::TYPE_SET => '%s__%s[]',
            Column::TYPE_POINT => 'point',
            Column::TYPE_LINE => 'line',
            Column::TYPE_POLYGON => 'polygon',
        ];
    }

    protected array $defaultLength = [
        Column::TYPE_BIT => 32,
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
        Column::TYPE_NUMERIC => [10, 0],
        Column::TYPE_DECIMAL => [10, 0],
    ];

    /** @var array<string, string> */
    private array $typeCastMap = [
        Column::TYPE_STRING => 'varchar',
    ];

    public function createTable(MigrationTable $table): array
    {
        $queries = [];
        $enumSetColumns = [];
        /** @var Column|null $autoIncrementColumn */
        $autoIncrementColumn = null;
        foreach ($table->getColumns() as $column) {
            if (in_array($column->getType(), [Column::TYPE_ENUM, Column::TYPE_SET], true)) {
                $enumSetColumns[] = $column;
            }
            if ($column->getSettings()->isAutoincrement()) {
                $autoIncrementColumn = $column;
            }
        }

        if (!empty($enumSetColumns)) {
            foreach ($enumSetColumns as $column) {
                $queries[] = 'CREATE TYPE ' . $this->escapeString($table->getName() . '__' . $column->getName()) . ' AS ENUM (' . implode(',', array_map(function ($value) {
                    return "'$value'";
                }, $column->getSettings()->getValues() ?: [])) . ');';
            }
        }

        $queries[] = 'CREATE TABLE ' . $this->escapeString($table->getName()) . $this->createTableQuery($table);
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }
        $queries = array_merge($queries, $this->createComments($table));
        $autoIncrement = $table->getAutoIncrement();
        if ($autoIncrement !== null && $autoIncrementColumn !== null) {
            $sequenceName = $table->getName() . '_' . $autoIncrementColumn->getName() . '_seq';
            $queries[] = $this->createAutoIncrementQuery($sequenceName, $autoIncrement);
        }
        return $queries;
    }

    public function dropTable(MigrationTable $table): array
    {
        return [
            sprintf('DROP TABLE %s;', $this->escapeString($table->getName())),
            sprintf("DELETE FROM %s WHERE %s LIKE '%s';", $this->escapeString('pg_type'), $this->escapeString('typname'), $table->getName() . '__%'),
        ];
    }

    public function truncateTable(MigrationTable $table): array
    {
        return [sprintf('TRUNCATE TABLE %s', $this->escapeString($table->getName()))];
    }

    public function renameTable(MigrationTable $table): array
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($table->getNewName()) . ';'];
    }

    public function alterTable(MigrationTable $table): array
    {
        $queries = $this->dropIndexes($table);
        $queries = array_merge($queries, $this->dropKeys($table, 'CONSTRAINT ' . $this->escapeString($table->getName() . '_pkey'), 'CONSTRAINT'));
        if ($table->getUniqueConstraintsToDrop()) {
            $queries[] = $this->dropUniqueConstraints($table);
        }
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        $queries = array_merge($queries, $this->addColumns($table));
        if (!empty($table->getIndexes())) {
            foreach ($table->getIndexes() as $index) {
                $queries[] = $this->createIndex($index, $table);
            }
        }

        foreach ($table->getColumnsToRename() as $oldName => $newName) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME COLUMN ' . $this->escapeString($oldName) . ' TO ' . $this->escapeString($newName) . ';';
        }

        foreach ($table->getColumnsToChange() as $oldColumnName => $newColumn) {
            if ($oldColumnName !== $newColumn->getName()) {
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME COLUMN ' . $this->escapeString($oldColumnName) . ' TO ' . $this->escapeString($newColumn->getName()) . ';';
            }
            if (in_array($newColumn->getType(), [Column::TYPE_ENUM, Column::TYPE_SET], true)) {
                $cast = sprintf($this->remapType($newColumn), $table->getName(), $newColumn->getName());
                /** @var Table $tableInfo */
                $tableInfo = $this->adapter->getStructure()->getTable($table->getName());
                /** @var Column $column */
                $column = $tableInfo->getColumn($oldColumnName);
                /** @var mixed[] $columnValues */
                $columnValues = $column->getSettings()->getValues();
                /** @var mixed[] $newColumnValues */
                $newColumnValues = $newColumn->getSettings()->getValues();
                foreach (array_diff($columnValues, $newColumnValues) as $valueToDelete) {
                    $queries[] = sprintf("DELETE FROM pg_enum WHERE enumlabel = '%s' AND enumtypid IN (SELECT oid FROM pg_type WHERE typname = '%s')", $valueToDelete, $table->getName() . '__' . $newColumn->getName());
                }
                foreach (array_diff($newColumnValues, $columnValues) as $newValue) {
                    $queries[] = 'ALTER TYPE ' . $table->getName() . '__' . $newColumn->getName() . ' ADD VALUE \'' . $newValue . '\'';
                }
            } else {
                $cast = (isset($this->typeCastMap[$newColumn->getType()]) ? $this->typeCastMap[$newColumn->getType()] : $newColumn->getType());
            }
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' TYPE ' . $this->createType($newColumn, $table) . ' USING ' . $newColumn->getName() . '::' . $cast . ';';
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . ($newColumn->getSettings()->allowNull() ? 'DROP' : 'SET') . ' NOT NULL;';
            if ($newColumn->getSettings()->getDefault() === null && $newColumn->getSettings()->allowNull()) {
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'SET DEFAULT NULL;';
            } elseif ($newColumn->getSettings()->getDefault()) {
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'SET DEFAULT ' . $this->escapeDefault($newColumn) . ';';
            } else {
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'DROP DEFAULT;';
            }
            if ($newColumn->getSettings()->getComment() !== null) {
                $queries[] = $this->createColumnComment($table, $newColumn);
            }
        }

        $queries = array_merge($queries, $this->addPrimaryKey($table));
        $queries = array_merge($queries, $this->addForeignKeys($table));
        $queries = array_merge($queries, $this->addUniqueConstraints($table));
        if ($table->getComment() !== null) {
            $queries[] = $this->createTableComment($table);
        }
        if ($table->getAutoIncrement() !== null) {
            $queries[] = $this->createAutoIncrement($table);
        }
        return $queries;
    }

    public function copyTable(MigrationTable $table): array
    {
        if ($table->getCopyType() === MigrationTable::COPY_ONLY_DATA) {
            if ($table->getPrimaryColumnsValuesFunction() !== null) {
                return $this->copyAndAddData($table);
            }
            return ['INSERT INTO ' . $this->escapeString($table->getNewName()) . ' SELECT * FROM ' . $this->escapeString($table->getName()) . ';'];
        }

        $query = 'CREATE TABLE ' . $this->escapeString($table->getNewName()) . ' AS TABLE ' . $this->escapeString($table->getName()) . ' WITH';
        if ($table->getCopyType() === MigrationTable::COPY_ONLY_STRUCTURE) {
            $query .= ' NO';
        }
        $query .= ' DATA;';
        return [$query];
    }

    protected function createColumn(Column $column, MigrationTable $table): string
    {
        $col = $this->escapeString($column->getName()) . ' ';
        if ($column->getSettings()->isAutoincrement()) {
            $col .= $column->getType() === Column::TYPE_BIG_INTEGER ? 'bigserial' : ($column->getType() === Column::TYPE_SMALL_INTEGER ? 'smallserial' : 'serial');
        } else {
            $col .= $this->createType($column, $table);
        }

        if ($column->getSettings()->allowNull() && $column->getSettings()->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        } elseif ($column->getSettings()->getDefault() !== null || $column->getType() === Column::TYPE_BOOLEAN) {
            $col .= ' DEFAULT ' . $this->escapeDefault($column);
        }
        $col .= $column->getSettings()->allowNull() ? '' : ' NOT NULL';
        return $col;
    }

    private function escapeDefault(Column $column): string
    {
        if (in_array($column->getType(), [Column::TYPE_INTEGER, Column::TYPE_BIT], true)) {
            $default = (string)$column->getSettings()->getDefault();
        } elseif (in_array($column->getType(), [Column::TYPE_BOOLEAN], true)) {
            $default = $column->getSettings()->getDefault() ? 'true' : 'false';
        } elseif ($column->getType() === Column::TYPE_TIMESTAMP && $column->getSettings()->getDefault() === ColumnSettings::DEFAULT_VALUE_CURRENT_TIMESTAMP) {
            $default = 'CURRENT_TIMESTAMP';
        } else {
            $default = "'" . $this->sanitizeSingleQuote($column->getSettings()->getDefault()) . "'";
        }

        return $default;
    }

    protected function primaryKeyString(MigrationTable $table): string
    {
        $primaryKeys = [];
        foreach ($table->getPrimaryColumnNames() as $name) {
            $primaryKeys[] = $this->escapeString($name);
        }
        return 'CONSTRAINT ' . $this->escapeString($table->getName() . '_pkey') . ' PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndex(Index $index, MigrationTable $table): string
    {
        $columns = [];
        /** @var IndexColumn $indexColumn */
        foreach ($index->getColumns() as $indexColumn) {
            $indexColumnSettings = $indexColumn->getSettings()->getNonDefaultSettings();
            $name = $this->escapeString($indexColumn->getName());
            $lengthSetting = $indexColumnSettings[IndexColumnSettings::SETTING_LENGTH] ?? null;
            if ($lengthSetting) {
                $name = 'SUBSTRING(' . $name . ' FOR ' . $lengthSetting . ')';
            }
            $columnParts = [$name];
            $order = $indexColumnSettings[IndexColumnSettings::SETTING_ORDER] ?? null;
            if ($order) {
                $columnParts[] = $order;
            }
            $columns[] = implode(' ', $columnParts);
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $indexMethod = $index->getMethod() ? ' USING ' . $index->getMethod() : '';
        return 'CREATE ' . $indexType . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . $indexMethod . ' (' . implode(',', $columns) . ');';
    }

    protected function dropIndexes(MigrationTable $table): array
    {
        if (empty($table->getIndexesToDrop())) {
            return [];
        }
        $query = 'DROP INDEX ';
        $indexes = [];
        foreach ($table->getIndexesToDrop() as $index) {
            $indexes[] = $this->escapeString($index);
        }
        $query .= implode(',', $indexes) . ';';
        return [$query];
    }

    public function escapeString(?string $string): string
    {
        return '"' . $string . '"';
    }

    private function createAutoIncrement(MigrationTable $table): string
    {
        /** @var PgsqlAdapter $adapter */
        $adapter = $this->adapter;
        $sequenceName = $adapter->getSequenceName($table);
        if ($sequenceName === null) {
            throw new InvalidArgumentException('Table ' . $table->getName() . ' has no sequence, so you cannot set auto increment');
        }
        /** @var int $autoincrement */
        $autoincrement = $table->getAutoIncrement();
        return $this->createAutoIncrementQuery($sequenceName, $autoincrement);
    }

    private function createAutoIncrementQuery(string $sequenceName, int $autoIncrement): string
    {
        return sprintf('ALTER SEQUENCE %s RESTART WITH %d;', $this->escapeString($sequenceName), $autoIncrement);
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table): string
    {
        return sprintf($this->remapType($column), $table->getName(), $column->getName());
    }

    /**
     * @param MigrationTable $table
     * @return string[]
     */
    private function createComments(MigrationTable $table): array
    {
        $queries = [];
        if ($table->getComment() !== null) {
            $queries[] = $this->createTableComment($table);
        }
        foreach ($table->getColumns() as $column) {
            if ($column->getSettings()->getComment() !== null) {
                $queries[] = $this->createColumnComment($table, $column);
            }
        }
        return $queries;
    }

    private function createTableComment(MigrationTable $table): string
    {
        $comment = $this->sanitizeSingleQuote((string)$table->getComment());
        return "COMMENT ON TABLE {$table->getName()} IS '$comment';";
    }

    private function createColumnComment(MigrationTable $table, Column $column): string
    {
        $comment = $this->sanitizeSingleQuote((string)$column->getSettings()->getComment());
        return "COMMENT ON COLUMN {$table->getName()}.{$column->getName()} IS '$comment';";
    }

    private function sanitizeSingleQuote(string $input): string
    {
        return str_replace("'", "''", $input);
    }
}
