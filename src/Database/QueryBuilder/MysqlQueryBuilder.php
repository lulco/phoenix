<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;

class MysqlQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_TINY_INTEGER => 'tinyint(%d)',
        Column::TYPE_SMALL_INTEGER => 'smallint(%d)',
        Column::TYPE_MEDIUM_INTEGER => 'mediumint(%d)',
        Column::TYPE_INTEGER => 'int(%d)',
        Column::TYPE_BIG_INTEGER => 'bigint(%d)',
        Column::TYPE_BOOLEAN => 'tinyint(1)',
        Column::TYPE_BINARY => 'binary(%d)',
        Column::TYPE_VARBINARY => 'varbinary(%d)',
        Column::TYPE_TINY_TEXT => 'tinytext',
        Column::TYPE_MEDIUM_TEXT => 'mediumtext',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_LONG_TEXT => 'longtext',
        Column::TYPE_TINY_BLOB => 'tinyblob',
        Column::TYPE_MEDIUM_BLOB => 'mediumblob',
        Column::TYPE_BLOB => 'blob',
        Column::TYPE_LONG_BLOB => 'longblob',
        Column::TYPE_DATE => 'date',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_UUID => 'char(36)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_NUMERIC => 'decimal(%d,%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
        Column::TYPE_FLOAT => 'float(%d,%d)',
        Column::TYPE_DOUBLE => 'double(%d,%d)',
        Column::TYPE_ENUM => 'enum(%s)',
        Column::TYPE_SET => 'set(%s)',
        Column::TYPE_POINT => 'point',
        Column::TYPE_LINE => 'linestring',
        Column::TYPE_POLYGON => 'polygon',
    ];

    protected $defaultLength = [
        Column::TYPE_STRING => 255,
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

    /**
     * generates create table query for mysql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function createTable(MigrationTable $table)
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
        $query .= ')';
        $query .= $this->createTableCharset($table);
        $query .= ';';
        return [$query];
    }

    /**
     * generates drop table query for mysql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function dropTable(MigrationTable $table)
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }

    /**
     * generates rename table queries for mysql
     * @param MigrationTable $table
     * @param string $newTableName
     * @return array list of queries
     */
    public function renameTable(MigrationTable $table, $newTableName)
    {
        return ['RENAME TABLE ' . $this->escapeString($table->getName())  . ' TO ' . $this->escapeString($newTableName) . ';'];
    }

    /**
     * generates alter table query for mysql
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function alterTable(MigrationTable $table)
    {
        $queries = $this->dropIndexes($table);
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
        $queries = array_merge($queries, $this->addColumns($table));
        $queries = array_merge($queries, $this->addPrimaryKey($table));
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
        return $queries;
    }

    protected function createColumn(Column $column, MigrationTable $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column, $table);
        $col .= (!$column->isSigned()) ? ' unsigned' : '';
        $col .= $this->createColumnCharset($column);
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        $col .= $this->createColumnDefault($column);
        $col .= $this->createColumnPosition($column);

        $col .= $column->isAutoincrement() ? ' AUTO_INCREMENT' : '';
        return $col;
    }

    private function createColumnDefault(Column $column)
    {
        if ($column->allowNull() && $column->getDefault() === null) {
            return ' DEFAULT NULL';
        }

        if ($column->getDefault() !== null) {
            $default = ' DEFAULT ';
            if ($column->getType() == Column::TYPE_INTEGER) {
                return $default .= $column->getDefault();
            }
            if ($column->getType() == Column::TYPE_BOOLEAN) {
                return $default .= intval($column->getDefault());
            }
            return $default .= "'" . $column->getDefault() . "'";
        }

        return '';
    }

    private function createColumnPosition(Column $column)
    {
        if ($column->getAfter() !== null) {
            return ' AFTER ' . $this->escapeString($column->getAfter());
        }
        if ($column->isFirst()) {
            return  ' FIRST';
        }
        return '';
    }

    protected function primaryKeyString(MigrationTable $table)
    {
        $primaryKeys = $this->escapeArray($table->getPrimaryColumns());
        return 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }

    private function createIndexes(MigrationTable $table)
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

    private function createIndex(Index $index)
    {
        $columns = $this->escapeArray($index->getColumns());
        $indexType = $index->getType() ? $index->getType() . ' INDEX' : 'INDEX';
        $indexMethod = $index->getMethod() ? ' USING ' . $index->getMethod() : '';
        return $indexType . ' ' . $this->escapeString($index->getName()) . ' (' . implode(',', $columns) . ')' . $indexMethod;
    }

    public function escapeString($string)
    {
        return '`' . $string . '`';
    }

    private function createColumnCharset(Column $column)
    {
        return $this->createCharset($column->getCharset(), $column->getCollation(), ' ');
    }

    private function createTableCharset(MigrationTable $table)
    {
        $tableCharset = $this->createCharset($table->getCharset(), $table->getCollation());
        return $tableCharset ? ' DEFAULT' . $tableCharset : '';
    }

    private function createCharset($charset = null, $collation = null, $glue = '=')
    {
        $output = '';
        if (is_null($charset) && is_null($collation)) {
            return $output;
        }
        if ($charset) {
            $output .= " CHARACTER SET$glue$charset";
        }
        if ($collation) {
            $output .= " COLLATE$glue$collation";
        }
        return $output;
    }

    protected function createEnumSetColumn(Column $column, MigrationTable $table)
    {
        return sprintf(
            $this->remapType($column),
            implode(',', array_map(function ($value) {
                return "'$value'";
            }, $column->getValues()))
        );
    }
}
