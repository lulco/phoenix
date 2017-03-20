<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Exception\PhoenixException;

class PgsqlQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_TINY_INTEGER => 'smallint',
        Column::TYPE_SMALL_INTEGER => 'smallint',
        Column::TYPE_MEDIUM_INTEGER => 'int4',
        Column::TYPE_INTEGER => 'int4',
        Column::TYPE_BIG_INTEGER => 'int8',
        Column::TYPE_BOOLEAN => 'bool',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATE => 'date',
        Column::TYPE_DATETIME => 'timestamp(6)',
        Column::TYPE_UUID => 'uuid',
        Column::TYPE_JSON => 'json',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
        Column::TYPE_FLOAT => 'real',
        Column::TYPE_DOUBLE => 'double precision',
        Column::TYPE_ENUM => '%s__%s',
        Column::TYPE_SET => '%s__%s[]',
    ];

    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
        Column::TYPE_DECIMAL => [10, 0],
    ];

    private $typeCastMap = [
        Column::TYPE_STRING => 'varchar',
    ];

    private $adapter;

    public function __construct(AdapterInterface $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * generates create table query for pgsql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function createTable(MigrationTable $table)
    {
        $queries = [];
        $primaryKeys = $table->getPrimaryColumns();
        $enumSetColumns = [];
        foreach ($table->getColumns() as $column) {
            if (in_array($column->getType(), [Column::TYPE_ENUM, Column::TYPE_SET])) {
                $enumSetColumns[] = $column;
            }
        }

        if (!empty($enumSetColumns)) {
            foreach ($enumSetColumns as $column) {
                $queries[] = 'CREATE TYPE ' . $this->escapeString($table->getName() . '__' . $column->getName()) . ' AS ENUM (' . implode(',', array_map(function ($value) {
                    return "'$value'";
                }, $column->getValues())) . ');';
            }
        }

        $autoincrement = false;
        foreach ($primaryKeys as $primaryKey) {
            $primaryKeyColumn = $table->getColumn($primaryKey);
            if ($primaryKeyColumn->isAutoincrement()) {
                $autoincrement = true;
                break;
            }
        }
        if ($autoincrement) {
            $queries[] = 'CREATE SEQUENCE ' . $this->escapeString($table->getName() . '_seq') . ';';
        }

        $queries[] = $this->createTableQuery($table);
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }

        return $queries;
    }

    /**
     * generates drop table query for pgsql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function dropTable(MigrationTable $table)
    {
        return [
            'DROP TABLE ' . $this->escapeString($table->getName()) . ';',
            'DROP SEQUENCE IF EXISTS ' . $this->escapeString($table->getName() . '_seq') . ';',
            'DELETE FROM ' . $this->escapeString('pg_type') . ' WHERE ' . $this->escapeString('typname') . ' LIKE \'' . $table->getName() . '__%\';',
        ];
    }

    /**
     * generates rename table queries for pgsql
     * @param MigrationTable $table
     * @param string $newTableName
     * @return array list of queries
     */
    public function renameTable(MigrationTable $table, $newTableName)
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($newTableName) . ';'];
    }

    /**
     * generates alter table query for pgsql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function alterTable(MigrationTable $table)
    {
        $queries = $this->dropIndexes($table);
        $queries = array_merge($queries, $this->dropKeys($table, 'CONSTRAINT ' . $this->escapeString($table->getName() . '_pkey'), 'CONSTRAINT'));
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        $queries = array_merge($queries, $this->addColumns($table));
        if (!empty($table->getIndexes())) {
            foreach ($table->getIndexes() as $index) {
                $queries[] = $this->createIndex($index, $table);
            }
        }

        if ($table->getColumnsToChange()) {
            foreach ($table->getColumnsToChange() as $oldColumnName => $newColumn) {
                if ($oldColumnName != $newColumn->getName()) {
                    $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME COLUMN ' . $this->escapeString($oldColumnName) . ' TO ' . $this->escapeString($newColumn->getName()) . ';';
                }
                if (in_array($newColumn->getType(), [Column::TYPE_ENUM, Column::TYPE_SET])) {
                    if ($this->adapter === null) {
                        throw new PhoenixException('Missing adapter');
                    }

                    $cast = sprintf($this->remapType($newColumn), $table->getName(), $newColumn->getName());

                    $tableInfo = $this->adapter->tableInfo($table->getName());
                    foreach (array_diff($newColumn->getValues(), $tableInfo[$oldColumnName]->getValues()) as $newValue) {
                        $queries[] = 'ALTER TYPE ' . $table->getName() . '__' . $newColumn->getName() . ' ADD VALUE \'' . $newValue . '\'';
                    }
                } else {
                    $cast = (isset($this->typeCastMap[$newColumn->getType()]) ? $this->typeCastMap[$newColumn->getType()] : $newColumn->getType());
                }
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' TYPE ' . $this->createType($newColumn, $table) . ' USING ' . $newColumn->getName() . '::' . $cast . ';';
                $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . ($newColumn->allowNull() ? 'DROP' : 'SET') . ' NOT NULL;';
                if ($newColumn->getDefault() === null && $newColumn->allowNull()) {
                    $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'SET DEFAULT NULL;';
                } elseif ($newColumn->getDefault()) {
                    $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'SET DEFAULT ' . $this->escapeDefault($newColumn, $table) . ';';
                } else {
                    $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ALTER COLUMN ' . $this->escapeString($newColumn->getName()) . ' ' . 'DROP DEFAULT;';
                }
            }
        }
        $queries = array_merge($queries, $this->addPrimaryKey($table));
        $queries = array_merge($queries, $this->addForeignKeys($table));
        return $queries;
    }

    protected function createColumn(Column $column, MigrationTable $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        if ($column->getDefault() !== null || $column->isAutoincrement()) {
            $col .= ' DEFAULT ' . $this->escapeDefault($column, $table);
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        return $col;
    }

    private function escapeDefault(Column $column, MigrationTable $table)
    {
        if ($column->isAutoincrement()) {
            $default = "nextval('" . $table->getName() . "_seq'::regclass)";
        } elseif ($column->getType() == Column::TYPE_INTEGER) {
            $default = $column->getDefault();
        } elseif ($column->getType() == Column::TYPE_BOOLEAN) {
            $default = $column->getDefault() ? 'true' : 'false';
        } else {
            $default = "'" . $column->getDefault() . "'";
        }

        return $default;
    }

    protected function primaryKeyString(MigrationTable $table)
    {
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $primaryKeys[] = $this->escapeString($name);
        }
        return 'CONSTRAINT ' . $this->escapeString($table->getName() . '_pkey') . ' PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndex(Index $index, MigrationTable $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $indexMethod = $index->getMethod() ? ' USING ' . $index->getMethod() : '';
        return 'CREATE ' . $indexType . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . $indexMethod . ' (' . implode(',', $columns) . ');';
    }

    protected function dropIndexes(MigrationTable $table)
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

    public function escapeString($string)
    {
        return '"' . $string . '"';
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table)
    {
        return sprintf($this->remapType($column), $table->getName(), $column->getName());
    }
}
