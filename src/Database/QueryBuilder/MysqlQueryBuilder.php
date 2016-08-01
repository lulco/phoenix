<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;

class MysqlQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_INTEGER => 'int(%d)',
        Column::TYPE_BIG_INTEGER => 'bigint(%d)',
        Column::TYPE_BOOLEAN => 'tinyint(1)',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATE => 'date',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_UUID => 'char(36)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
        Column::TYPE_FLOAT => 'float(%d,%d)',
    ];

    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_INTEGER => 11,
        Column::TYPE_BIG_INTEGER => 20,
        Column::TYPE_CHAR => 255,
        Column::TYPE_DECIMAL => [10, 0],
        Column::TYPE_FLOAT => [10, 0],
    ];
    
    /**
     * generates create table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function createTable(Table $table)
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
     * @param Table $table
     * @return array list of queries
     */
    public function dropTable(Table $table)
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }
    
    /**
     * generates rename table queries for mysql
     * @param Table $table
     * @param string $newTableName
     * @return array list of queries
     */
    public function renameTable(Table $table, $newTableName)
    {
        return ['RENAME TABLE ' . $this->escapeString($table->getName())  . ' TO ' . $this->escapeString($newTableName) . ';'];
    }
    
    /**
     * generates alter table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function alterTable(Table $table)
    {
        $queries = [];
        if (!empty($table->getIndexesToDrop())) {
            $queries[] = $this->dropIndexes($table);
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
        
        if ($table->hasPrimaryKeyToDrop()) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP PRIMARY KEY;';
        }
        
        foreach ($table->getForeignKeysToDrop() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP FOREIGN KEY ' . $this->escapeString($foreignKey) . ';';
        }
        
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        
        $columns = $table->getColumns();
        if (!empty($columns)) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($columns as $column) {
                $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        
        $primaryColumns = $table->getPrimaryColumns();
        if (!empty($primaryColumns)) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->primaryKeyString($primaryColumns) . ';';
        }
        
        if (!empty($table->getIndexes())) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $indexes = [];
            foreach ($table->getIndexes() as $index) {
                $indexes[] = 'ADD ' . $this->createIndex($index);
            }
            $query .= implode(',', $indexes) . ';';
            $queries[] = $query;
        }
        
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }
    
    protected function createColumn(Column $column, Table $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column);
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
    
    protected function createPrimaryKey(Table $table)
    {
        return $this->primaryKeyString($table->getPrimaryColumns());
    }
    
    private function primaryKeyString(array $primaryColumns = [])
    {
        if (empty($primaryColumns)) {
            return '';
        }
        
        $primaryKeys = $this->escapeArray($primaryColumns);
        return 'PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndexes(Table $table)
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
        return $index->getType() . ' ' . $this->escapeString($index->getName()) . ' (' . implode(',', $columns) . ')' . (!$index->getMethod() ? '' : ' ' . $index->getMethod());
    }
    
    public function escapeString($string)
    {
        return '`' . $string . '`';
    }
    
    private function createColumnCharset(Column $column)
    {
        return $this->createCharset($column->getCharset(), $column->getCollation(), ' ');
    }
    
    private function createTableCharset(Table $table)
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
}
