<?php

declare(strict_types=1);

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\IndexColumnSettings;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Table;

final class MysqlQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    public const FEATURE_JSON = 'json';

    /** @var string[] */
    private array $features;

    /**
     * @param string[] $features
     */
    public function __construct(AdapterInterface $adapter, array $features = [])
    {
        parent::__construct($adapter);
        $this->features = $features;
    }

    protected function typeMap(): array
    {
        $typeMap = [
            Column::TYPE_BIT => 'bit(%d)',
            Column::TYPE_TINY_INTEGER => 'tinyint(%d)',
            Column::TYPE_SMALL_INTEGER => 'smallint(%d)',
            Column::TYPE_MEDIUM_INTEGER => 'mediumint(%d)',
            Column::TYPE_INTEGER => 'int(%d)',
            Column::TYPE_BIG_INTEGER => 'bigint(%d)',
            Column::TYPE_NUMERIC => 'decimal(%d,%d)',
            Column::TYPE_DECIMAL => 'decimal(%d,%d)',
            Column::TYPE_FLOAT => 'float(%d,%d)',
            Column::TYPE_DOUBLE => 'double(%d,%d)',
            Column::TYPE_BINARY => 'binary(%d)',
            Column::TYPE_VARBINARY => 'varbinary(%d)',
            Column::TYPE_CHAR => 'char(%d)',
            Column::TYPE_STRING => 'varchar(%d)',
            Column::TYPE_BOOLEAN => 'tinyint(1)',
            Column::TYPE_DATE => 'date',
            Column::TYPE_DATETIME => 'datetime',
            Column::TYPE_TINY_TEXT => 'tinytext',
            Column::TYPE_MEDIUM_TEXT => 'mediumtext',
            Column::TYPE_TEXT => 'text',
            Column::TYPE_LONG_TEXT => 'longtext',
            Column::TYPE_TINY_BLOB => 'tinyblob',
            Column::TYPE_MEDIUM_BLOB => 'mediumblob',
            Column::TYPE_BLOB => 'blob',
            Column::TYPE_LONG_BLOB => 'longblob',
            Column::TYPE_UUID => 'char(36)',
            Column::TYPE_JSON => 'text',
            Column::TYPE_ENUM => 'enum(%s)',
            Column::TYPE_SET => 'set(%s)',
            Column::TYPE_POINT => 'point',
            Column::TYPE_LINE => 'linestring',
            Column::TYPE_POLYGON => 'polygon',
        ];

        if (in_array(self::FEATURE_JSON, $this->features, true)) {
            $typeMap[Column::TYPE_JSON] = 'json';
        }
        return $typeMap;
    }

    protected array $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_BIT => 32,
        Column::TYPE_TINY_INTEGER => 4,
        Column::TYPE_SMALL_INTEGER => 6,
        Column::TYPE_MEDIUM_INTEGER => 9,
        Column::TYPE_INTEGER => 11,
        Column::TYPE_BINARY => 255,
        Column::TYPE_VARBINARY => 255,
        Column::TYPE_BIG_INTEGER => 20,
        Column::TYPE_CHAR => 255,
        Column::TYPE_NUMERIC => [10, 0],
        Column::TYPE_DECIMAL => [10, 0],
        Column::TYPE_FLOAT => [10, 0],
        Column::TYPE_DOUBLE => [10, 0],
    ];

    public function createTable(MigrationTable $table): array
    {
        $query = 'CREATE TABLE ' . $this->escapeString($table->getName()) . ' (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column, $table);
        }
        $query .= implode(',', $columns);
        $primaryKey = $this->createPrimaryKey($table);
        $query .= $primaryKey ? ',' . $primaryKey : '';
        $query .= $this->createIndexes($table);
        $query .= $this->createForeignKeys($table);
        $query .= $this->createUniqueConstraints($table);
        $query .= ')';
        $query .= $this->createTableCharset($table);
        $query .= $this->createTableComment($table);
        $query .= ';';

        $queries = [$query];
        if ($table->getAutoIncrement() !== null) {
            $queries[] = $this->createAutoIncrement($table);
        }
        return $queries;
    }

    public function dropTable(MigrationTable $table): array
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }

    public function truncateTable(MigrationTable $table): array
    {
        return [sprintf('TRUNCATE TABLE %s', $this->escapeString($table->getName()))];
    }

    public function renameTable(MigrationTable $table): array
    {
        return ['RENAME TABLE ' . $this->escapeString($table->getName()) . ' TO ' . $this->escapeString($table->getNewName()) . ';'];
    }

    public function alterTable(MigrationTable $table): array
    {
        $queries = $this->dropIndexes($table);

        $tableStructure = $this->adapter->getStructure()->getTable($table->getName());
        if ($tableStructure && (($table->getCharset() && $table->getCharset() !== $tableStructure->getCharset()) || ($table->getCollation() && $table->getCollation() !== $tableStructure->getCollation()))) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . $this->createCharset($table->getCharset(), $table->getCollation()) . ';';
        }
        if ($tableStructure && $table->getColumnsToRename()) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($table->getColumnsToRename() as $oldName => $newName) {
                $column = $tableStructure->getColumn($oldName);
                if (!$column) {
                    continue;
                }
                $newColumn = new Column($newName, $column->getType(), $column->getSettings()->getSettings());
                $columnList[] = 'CHANGE COLUMN ' . $this->escapeString($oldName) . ' ' . $this->createColumn($newColumn, $table);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        if ($table->getColumnsToChange()) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($table->getColumnsToChange() as $oldName => $column) {
                $columnList[] = 'CHANGE COLUMN ' . $this->escapeString($oldName) . ' ' . $this->createColumn($column, $table);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        $queries = array_merge($queries, $this->dropKeys($table, 'PRIMARY KEY', 'FOREIGN KEY'));
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        if ($table->getUniqueConstraintsToDrop()) {
            $queries[] = $this->dropUniqueConstraints($table);
        }
        $queries = array_merge($queries, $this->addColumns($table));
        $queries = array_merge($queries, $this->addPrimaryKey($table));
        $queries = array_merge($queries, $this->addUniqueConstraints($table));
        if (!empty($table->getIndexes())) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $indexes = [];
            foreach ($table->getIndexes() as $index) {
                $indexes[] = 'ADD ' . $this->createIndex($index);
            }
            $query .= implode(',', $indexes) . ';';
            $queries[] = $query;
        }
        $queries = array_merge($queries, $this->addForeignKeys($table));
        if ($table->getComment() !== null) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . $this->createTableComment($table) . ';';
        }
        if ($table->getAutoIncrement() !== null) {
            $queries[] = $this->createAutoIncrement($table);
        }
        return $queries;
    }

    public function copyTable(MigrationTable $table): array
    {
        $queries = [];
        if ($table->getCopyType() !== MigrationTable::COPY_ONLY_DATA) {
            $queries[] = 'CREATE TABLE ' . $this->escapeString($table->getNewName()) . ' LIKE ' . $this->escapeString($table->getName()) . ';';
        }
        if ($table->getCopyType() !== MigrationTable::COPY_ONLY_STRUCTURE) {
            if ($table->getPrimaryColumnsValuesFunction() !== null) {
                $queries = array_merge($queries, $this->copyAndAddData($table));
                return $queries;
            }

            /** @var Table $oldTable */
            $oldTable = $this->adapter->getStructure()->getTable($table->getName());
            $columns = [];
            foreach ($oldTable->getColumns() as $column) {
                $columns[] = $column->getName();
            }
            $queries[] = 'INSERT INTO ' . $this->escapeString($table->getNewName()) . ' (' . implode(',', $columns) . ') SELECT ' . implode(',', $columns) . ' FROM ' . $this->escapeString($table->getName()) . ';';
        }
        return $queries;
    }

    protected function createColumn(Column $column, MigrationTable $table): string
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        $col .= (!$column->getSettings()->isSigned()) ? ' unsigned' : '';
        $col .= $this->createColumnCharset($column);
        $col .= $column->getSettings()->allowNull() ? '' : ' NOT NULL';
        $col .= $this->createComment($column->getSettings()->getComment(), ' ');
        $col .= $this->createColumnDefault($column);
        $col .= $column->getSettings()->isAutoincrement() ? ' AUTO_INCREMENT' : '';
        $col .= $this->createColumnPosition($column);

        return $col;
    }

    private function createColumnDefault(Column $column): string
    {
        if ($column->getSettings()->allowNull() && $column->getSettings()->getDefault() === null) {
            if ($column->getType() === Column::TYPE_TIMESTAMP) {
                return ' NULL DEFAULT NULL';
            }
            return ' DEFAULT NULL';
        }

        if ($column->getSettings()->getDefault() !== null || $column->getType() === Column::TYPE_BOOLEAN) {
            $default = ' DEFAULT ';
            if (in_array($column->getType(), [Column::TYPE_INTEGER, Column::TYPE_BIT], true)) {
                return $default . $column->getSettings()->getDefault();
            }
            if (in_array($column->getType(), [Column::TYPE_BOOLEAN], true)) {
                return $default . intval($column->getSettings()->getDefault());
            }
            if (($column->getType() === Column::TYPE_TIMESTAMP || $column->getType() === Column::TYPE_DATETIME) && $column->getSettings()->getDefault() === ColumnSettings::DEFAULT_VALUE_CURRENT_TIMESTAMP) {
                if ($column->getSettings()->allowNull()) {
                    $default = ' NULL' . $default;
                }
                return $default . 'CURRENT_TIMESTAMP';
            }
            return $default . "'" . $this->sanitizeSingleQuote($column->getSettings()->getDefault()) . "'";
        }

        return '';
    }

    private function createColumnPosition(Column $column): string
    {
        if ($column->getSettings()->getAfter() !== null) {
            return ' AFTER ' . $this->escapeString($column->getSettings()->getAfter());
        }
        if ($column->getSettings()->isFirst()) {
            return ' FIRST';
        }
        return '';
    }

    protected function primaryKeyString(MigrationTable $table): string
    {
        $primaryKeys = $this->escapeArray($table->getPrimaryColumnNames());
        return 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndexes(MigrationTable $table): string
    {
        if (empty($table->getIndexes())) {
            return '';
        }

        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $indexes[] = $this->createIndex($index);
        }
        return ',' . implode(',', $indexes);
    }

    private function createIndex(Index $index): string
    {
        $columns = [];
        /** @var IndexColumn $indexColumn */
        foreach ($index->getColumns() as $indexColumn) {
            $indexColumnSettings = $indexColumn->getSettings()->getNonDefaultSettings();
            $lengthSetting = $indexColumnSettings[IndexColumnSettings::SETTING_LENGTH] ?? null;
            $length = '';
            if ($lengthSetting) {
                $length = '(' . $lengthSetting . ')';
            }
            $columnParts = [$this->escapeString($indexColumn->getName()) . $length];
            $order = $indexColumnSettings[IndexColumnSettings::SETTING_ORDER] ?? null;
            if ($order) {
                $columnParts[] = $order;
            }
            $columns[] = implode(' ', $columnParts);
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $indexMethod = $index->getMethod() ? ' USING ' . $index->getMethod() : '';
        return $indexType . ' ' . $this->escapeString($index->getName()) . ' (' . implode(',', $columns) . ')' . $indexMethod;
    }

    public function escapeString(?string $string): string
    {
        return '`' . $string . '`';
    }

    private function createAutoIncrement(MigrationTable $table): string
    {
        return sprintf('ALTER TABLE %s AUTO_INCREMENT=%d;', $this->escapeString($table->getName()), $table->getAutoIncrement());
    }

    private function createColumnCharset(Column $column): string
    {
        return $this->createCharset($column->getSettings()->getCharset(), $column->getSettings()->getCollation(), ' ');
    }

    private function createTableCharset(MigrationTable $table): string
    {
        $tableCharset = $this->createCharset($table->getCharset(), $table->getCollation());
        return $tableCharset ? ' DEFAULT' . $tableCharset : '';
    }

    private function createCharset(?string $charset = null, ?string $collation = null, string $glue = '='): string
    {
        if ($charset === null && $collation === null) {
            return '';
        }
        $charsetAndCollation = '';
        if ($charset) {
            $charsetAndCollation .= " CHARACTER SET$glue$charset";
        }
        if ($collation) {
            $charsetAndCollation .= " COLLATE$glue$collation";
        }
        return $charsetAndCollation;
    }

    private function createTableComment(MigrationTable $table): string
    {
        return $this->createComment($table->getComment());
    }

    private function createComment(?string $comment = null, string $glue = '='): string
    {
        if ($comment === null) {
            return '';
        }
        $comment = $this->sanitizeSingleQuote($comment);
        return " COMMENT$glue'$comment'";
    }

    private function sanitizeSingleQuote(string $input): string
    {
        return str_replace("'", "\'", $input);
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table): string
    {
        return sprintf(
            $this->remapType($column),
            implode(',', array_map(function ($value) {
                return "'$value'";
            }, $column->getSettings()->getValues() ?: []))
        );
    }
}
