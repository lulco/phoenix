<?php

declare(strict_types=1);

namespace Phoenix\Database\QueryBuilder;

use InvalidArgumentException;
use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\MigrationView;
use Phoenix\Database\Element\UniqueConstraint;

abstract class CommonQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string, int|array{int, int}> */
    protected array $defaultLength = [];

    protected AdapterInterface $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    protected function createType(Column $column, MigrationTable $table): string
    {
        if (in_array($column->getType(), [Column::TYPE_NUMERIC, Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE], true)) {
            /** @var array{?int, ?int} $lengthAndDecimals */
            $lengthAndDecimals = $this->defaultLength[$column->getType()] ?? [null, null];
            [$length, $decimals] = $lengthAndDecimals;
            return sprintf(
                $this->remapType($column),
                $column->getSettings()->getLength($length),
                $column->getSettings()->getDecimals($decimals)
            );
        } elseif (in_array($column->getType(), [Column::TYPE_ENUM, Column::TYPE_SET], true)) {
            return $this->createEnumSetColumn($column, $table);
        }
        /** @var ?int $length */
        $length = $this->defaultLength[$column->getType()] ?? null;
        return sprintf($this->remapType($column), $column->getSettings()->getLength($length));
    }

    protected function remapType(Column $column): string
    {
        $typeMap = $this->typeMap();
        return $typeMap[$column->getType()] ?? $column->getType();
    }

    protected function createTableQuery(MigrationTable $table): string
    {
        $query = ' (';
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $this->createColumn($column, $table);
        }
        $query .= implode(',', $columns);
        $primaryKey = $this->createPrimaryKey($table);
        $query .= $primaryKey ? ',' . $primaryKey : '';
        $query .= $this->createForeignKeys($table);
        $query .= $this->createUniqueConstraints($table);
        $query .= ');';
        return $query;
    }

    /**
     * @return string[]
     */
    protected function addColumns(MigrationTable $table): array
    {
        $columns = $table->getColumns();
        if (empty($columns)) {
            return [];
        }
        return [$this->addColumnsQuery($table, $columns) . ';'];
    }

    /**
     * @param Column[] $columns
     */
    protected function addColumnsQuery(MigrationTable $table, array $columns): string
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columnList = [];
        foreach ($columns as $column) {
            $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
        }
        $query .= implode(',', $columnList);
        return $query;
    }

    protected function createPrimaryKey(MigrationTable $table): string
    {
        if (empty($table->getPrimaryColumnNames())) {
            return '';
        }
        return $this->primaryKeyString($table);
    }

    /**
     * @return array<PDOStatement|string>
     */
    protected function addPrimaryKey(MigrationTable $table): array
    {
        $primaryColumns = $table->getPrimaryColumns();
        $primaryColumnNames = $table->getPrimaryColumnNames();
        // move to MigrationTable
        if (!empty($primaryColumns) && !empty($primaryColumnNames)) {
            throw new InvalidArgumentException('Cannot combine addPrimary() and addPrimaryColumns() in one migration');
        }
        if (empty($primaryColumns) && empty($primaryColumnNames)) {
            return [];
        }
        if (!empty($primaryColumnNames)) {
            return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->primaryKeyString($table) . ';'];
        }
        $copyTable = new MigrationTable($table->getName());
        $newTableName = '_' . $table->getName() . '_copy_' . date('YmdHis');
        $copyTable->copy($newTableName, MigrationTable::COPY_ONLY_STRUCTURE);
        $queries = $this->copyTable($copyTable);

        $newTable = new MigrationTable($newTableName);
        $newTable->addPrimary($primaryColumns);
        $queries[] = $this->addColumnsQuery($newTable, $primaryColumns) . ',ADD ' . $this->primaryKeyString($newTable) . ';';

        $copyTable->addPrimaryColumns($table->getPrimaryColumns(), $table->getPrimaryColumnsValuesFunction(), $table->getDataChunkSize());
        $copyTable->copy($newTableName, MigrationTable::COPY_ONLY_DATA);
        $queries = array_merge($queries, $this->copyTable($copyTable));

        $tableToDeleteName = '_' . $table->getName() . '_to_delete_' . date('YmdHis');
        $table->rename($tableToDeleteName);
        $queries = array_merge($queries, $this->renameTable($table));

        $copyTable->setName($newTableName);
        $copyTable->rename($table->getName());
        $table->setName($tableToDeleteName);

        $queries = array_merge($queries, $this->renameTable($copyTable));
        $queries = array_merge($queries, $this->dropTable($table));

        return $queries;
    }

    /**
     * @return string[]
     */
    protected function dropIndexes(MigrationTable $table): array
    {
        if (empty($table->getIndexesToDrop())) {
            return [];
        }
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $indexes = [];
        foreach ($table->getIndexesToDrop() as $index) {
            $indexes[] = 'DROP INDEX ' . $this->escapeString($index);
        }
        $query .= implode(',', $indexes) . ';';
        return [$query];
    }

    protected function dropColumns(MigrationTable $table): string
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $columns = [];
        foreach ($table->getColumnsToDrop() as $column) {
            $columns[] = 'DROP COLUMN ' . $this->escapeString($column);
        }
        $query .= implode(',', $columns) . ';';
        return $query;
    }

    protected function createForeignKeys(MigrationTable $table): string
    {
        if (empty($table->getForeignKeys())) {
            return '';
        }

        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $foreignKeys[] = $this->createForeignKey($foreignKey, $table);
        }
        return ',' . implode(',', $foreignKeys);
    }

    protected function createUniqueConstraints(MigrationTable $table): string
    {
        if (!$table->getUniqueConstraints()) {
            return '';
        }

        $uniqueConstraints = [];
        foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
            $uniqueConstraints[] = $this->createUniqueConstraint($uniqueConstraint);
        }

        return ',' . implode(',', $uniqueConstraints);
    }

    /**
     * @return string[]
     */
    protected function addForeignKeys(MigrationTable $table): array
    {
        $queries = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }

    protected function createForeignKey(ForeignKey $foreignKey, MigrationTable $table): string
    {
        $columns = [];
        foreach ($foreignKey->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $referencedColumns = [];
        foreach ($foreignKey->getReferencedColumns() as $column) {
            $referencedColumns[] = $this->escapeString($column);
        }
        $constraint = 'CONSTRAINT ' . $this->escapeString($table->getName() . '_' . $foreignKey->getName());
        $constraint .= ' FOREIGN KEY (' . implode(',', $columns) . ')';
        $constraint .= ' REFERENCES ' . $this->escapeString($foreignKey->getReferencedTable()) . ' (' . implode(',', $referencedColumns) . ')';
        if ($foreignKey->getOnDelete() !== ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON DELETE ' . $foreignKey->getOnDelete();
        }
        if ($foreignKey->getOnUpdate() !== ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
        }
        return $constraint;
    }

    /**
     * @return string[]
     */
    protected function addUniqueConstraints(MigrationTable $table): array
    {
        $queries = [];
        foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createUniqueConstraint($uniqueConstraint) . ';';
        }

        return $queries;
    }

    protected function createUniqueConstraint(UniqueConstraint $uniqueConstraint): string
    {
        $columns = [];
        foreach ($uniqueConstraint->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }

        $constraint = 'CONSTRAINT ' . $this->escapeString($uniqueConstraint->getName());
        $constraint .= ' UNIQUE (' . implode(',', $columns) . ')';

        return $constraint;
    }

    /**
     * @return string[]
     */
    protected function dropKeys(MigrationTable $table, string $primaryKeyName, string $foreignKeyPrefix): array
    {
        $queries = [];
        if ($table->hasPrimaryKeyToDrop()) {
            $queries[] = $this->dropKeyQuery($table, $primaryKeyName);
        }
        foreach ($table->getForeignKeysToDrop() as $foreignKey) {
            $queries[] = $this->dropKeyQuery($table, $foreignKeyPrefix . ' ' . $this->escapeString($table->getName() . '_' . $foreignKey));
        }
        return $queries;
    }

    protected function dropUniqueConstraints(MigrationTable $table): string
    {
        $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
        $constraints = [];
        foreach ($table->getUniqueConstraintsToDrop() as $uniqueConstraint) {
            $constraints[] =  'DROP CONSTRAINT ' . $this->escapeString($uniqueConstraint);
        }
        $query .= implode(',', $constraints) . ';';

        return $query;
    }

    protected function dropKeyQuery(MigrationTable $table, string $key): string
    {
        return 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP ' . $key . ';';
    }

    /**
     * @return PDOStatement[]
     */
    protected function copyAndAddData(MigrationTable $table): array
    {
        $chunkSize = $table->getDataChunkSize();
        if ($chunkSize !== null) {
            $queries = [];
            /** @var array<int> $res */
            $res = $this->adapter->fetch($table->getName(), ['count(*) AS cnt']);
            $totalCount = $res['cnt'];
            $pages = ceil($totalCount / $chunkSize);
            for ($i = 0; $i < $pages; $i++) {
                $limit = $chunkSize . ' OFFSET ' . ($i * $chunkSize);
                $data = $this->adapter->fetchAll($table->getName(), ['*'], [], $limit);
                $queries[] = $this->createCopyAndAddDataQuery($table, $data);
            }
            return $queries;
        }

        $data = $this->adapter->fetchAll($table->getName());
        return empty($data) ? [] : [$this->createCopyAndAddDataQuery($table, $data)];
    }

    /**
     * @param array<array<string, mixed>> $oldData
     */
    private function createCopyAndAddDataQuery(MigrationTable $table, array $oldData): PDOStatement
    {
        $newData = [];
        foreach ($oldData as $row) {
            if ($table->getPrimaryColumnsValuesFunction() !== null) {
                $newData[] = call_user_func($table->getPrimaryColumnsValuesFunction(), $row);
            }
        }
        /** @var string $newTableName */
        $newTableName = $table->getNewName();
        return $this->adapter->buildInsertQuery($newTableName, $newData);
    }

    public function createView(MigrationView $view): array
    {
        $columns = array_map(function (string $column) {
            return $this->escapeString($column);
        }, $view->getColumns());
        return [
            'CREATE VIEW ' . $this->escapeString($view->getName()) . ($columns ? ' (' . implode(',', $columns) . ')' : '') . ' AS ' . $view->getSql(),
        ];
    }

    public function replaceView(MigrationView $view): array
    {
        $columns = array_map(function (string $column) {
            return $this->escapeString($column);
        }, $view->getColumns());
        return [
            'CREATE OR REPLACE VIEW ' . $this->escapeString($view->getName()) . ($columns ? ' (' . implode(',', $columns) . ')' : '') . ' AS ' . $view->getSql(),
        ];
    }

    public function dropView(MigrationView $view): array
    {
        return ['DROP VIEW ' . $this->escapeString($view->getName())];
    }

    abstract public function escapeString(?string $string): string;

    /**
     * @param string[] $array
     * @return string[]
     */
    protected function escapeArray(array $array): array
    {
        return array_map(function ($string) {
            return $this->escapeString($string);
        }, $array);
    }

    abstract protected function createColumn(Column $column, MigrationTable $table): string;

    abstract protected function primaryKeyString(MigrationTable $table): string;

    abstract protected function createEnumSetColumn(Column $column, MigrationTable $table): string;

    /**
     * @return array<string, string>
     */
    abstract protected function typeMap(): array;
}
