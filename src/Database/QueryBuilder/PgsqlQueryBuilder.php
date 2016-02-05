<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;

class PgsqlQueryBuilder implements QueryBuilderInterface
{
    use CommonQueryBuilder;
    
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_INTEGER => 'int4',
        Column::TYPE_BOOLEAN => 'bool',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATETIME => 'timestamp(6)',
        Column::TYPE_UUID => 'uuid',
        Column::TYPE_JSON => 'json',
        Column::TYPE_CHAR => 'char(%d)',
    ];
    
    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
    ];
    
    /**
     * generates create table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function createTable(Table $table)
    {
        $queries = [];
        $primaryKeys = $table->getPrimaryColumns();
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
     * generates drop table query for mysql
     * @param Table $table
     * @return array list of queries
     */
    public function dropTable(Table $table)
    {
        return [
            'DROP TABLE ' . $this->escapeString($table->getName()),
            'DROP SEQUENCE IF EXISTS ' . $this->escapeString($table->getName() . '_seq'),
        ];
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
        
        foreach ($table->getForeignKeysToDrop() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' DROP CONSTRAINT ' . $this->escapeString($foreignKey) . ';';
        }
        
        if (!empty($table->getColumnsToDrop())) {
            $queries[] = $this->dropColumns($table);
        }
        
        $columns = $table->getColumns();
        unset($columns['id']);
        if (!empty($columns)) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($columns as $column) {
                $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        
        if (!empty($table->getIndexes())) {
            foreach ($table->getIndexes() as $index) {
                $queries[] = $this->createIndex($index, $table);
            }
        }
        
        foreach ($table->getForeignKeys() as $foreignKey) {
            $queries[] = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ADD ' . $this->createForeignKey($foreignKey, $table) . ';';
        }
        return $queries;
    }
    
    private function createColumn(Column $column, Table $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column);
        if ($column->getDefault() !== null || $column->isAutoincrement()) {
            $col .= ' DEFAULT ';
            if ($column->isAutoincrement()) {
                $col .= "nextval('" . $table->getName() . "_seq'::regclass)";
            } elseif ($column->getType() == Column::TYPE_INTEGER) {
                $col .= $column->getDefault();
            } elseif ($column->getType() == Column::TYPE_BOOLEAN) {
                $col .= $column->getDefault() ? 'true' : 'false';
            } else {
                $col .= "'" . $column->getDefault() . "'";
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        return $col;
    }
    
    private function createPrimaryKey(Table $table)
    {
        if (empty($table->getPrimaryColumns())) {
            return '';
        }
        
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $primaryKeys[] = $this->escapeString($table->getColumn($name)->getName());
        }
        return ',CONSTRAINT ' . $this->escapeString($table->getName() . '_pkey') . ' PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndex(Index $index, Table $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($column);
        }
        return 'CREATE ' . $index->getType() . ' ' . $this->escapeString($table->getName() . '_' . $index->getName()) . ' ON ' . $this->escapeString($table->getName()) . (!$index->getMethod() ? '' : ' ' . $index->getMethod()) . ' (' . implode(',', $columns) . ');';
    }
    
    public function escapeString($string)
    {
        return '"' . $string . '"';
    }
}
