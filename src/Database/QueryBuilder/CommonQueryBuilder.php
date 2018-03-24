<?php

namespace Phoenix\Database\QueryBuilder;

use InvalidArgumentException;
use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;

abstract class CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [];

    protected $defaultLength = [];

    protected $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    protected function createType(Column $column, MigrationTable $table): string
    {
        if (in_array($column->getType(), [Column::TYPE_NUMERIC, Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE])) {
            return sprintf(
                $this->remapType($column),
                $column->getSettings()->getLength(isset($this->defaultLength[$column->getType()][0]) ? $this->defaultLength[$column->getType()][0] : null),
                $column->getSettings()->getDecimals(isset($this->defaultLength[$column->getType()][1]) ? $this->defaultLength[$column->getType()][1] : null)
            );
        } elseif (in_array($column->getType(), [Column::TYPE_ENUM, Column::TYPE_SET])) {
            return $this->createEnumSetColumn($column, $table);
        }
        return sprintf($this->remapType($column), $column->getSettings()->getLength(isset($this->defaultLength[$column->getType()]) ? $this->defaultLength[$column->getType()] : null));
    }

    protected function remapType(Column $column): string
    {
        return $this->typeMap[$column->getType()] ?? $column->getType();
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
        $query .= ');';
        return $query;
    }

    protected function addColumns(MigrationTable $table): array
    {
        $columns = $table->getColumns();
        if (empty($columns)) {
            return [];
        }
        return [$this->addColumnsQuery($table, $columns) . ';'];
    }

    protected function addColumnsQuery(MigrationTable $table, array $columns)
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

    protected function addPrimaryKey(MigrationTable $table): array
    {
        $primaryColumns = $table->getPrimaryColumns();
        $primaryColumnNames = $table->getPrimaryColumnNames();
        // TODO move to MigrationTable
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
        if ($foreignKey->getOnDelete() != ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON DELETE ' . $foreignKey->getOnDelete();
        }
        if ($foreignKey->getOnUpdate() != ForeignKey::DEFAULT_ACTION) {
            $constraint .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
        }
        return $constraint;
    }

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

    protected function dropKeyQuery(MigrationTable $table, string $key): string
    {
        return 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP ' . $key . ';';
    }

    protected function copyAndAddData(MigrationTable $table): array
    {
        $chunkSize = $table->getDataChunkSize();
        if ($chunkSize !== null) {
            $queries = [];
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

    private function createCopyAndAddDataQuery(MigrationTable $table, array $oldData): PDOStatement
    {
        $newData = [];
        foreach ($oldData as $row) {
            $newData[] = call_user_func($table->getPrimaryColumnsValuesFunction(), $row);
        }
        return $this->adapter->buildInsertQuery($table->getNewName(), $newData);
    }

    abstract public function escapeString(string $string): string;

    protected function escapeArray(array $array): array
    {
        return array_map(function ($string) {
            return $this->escapeString($string);
        }, $array);
    }

    abstract protected function createColumn(Column $column, MigrationTable $table): string;

    abstract protected function primaryKeyString(MigrationTable $table): string;

    abstract protected function createEnumSetColumn(Column $column, MigrationTable $table): string;
}
