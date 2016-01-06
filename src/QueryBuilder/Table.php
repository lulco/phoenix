<?php

namespace Phoenix\QueryBuilder;

use Exception;
use InvalidArgumentException;

class Table
{
    protected $name;
    
    protected $columns = [];
    
    protected $primaryColumns = [];
    
    protected $foreignKeys = [];
    
    protected $indexes = [];
    
    /**
     * @param string $name
     * @param mixed $primaryColumn
     * true - if you want classic autoincrement integer primary column with name id
     * Column - if you want to define your own column (column is added to list of columns)
     * string - name of column in list of columns
     * array of strings - names of columns in list of columns
     * array of Column - list of own columns (all columns are added to list of columns)
     * other (false, null) - if your table doesn't have primary key
     */
    public function __construct($name, $primaryColumn = true)
    {
        $this->name = $name;
        if ($primaryColumn === true) {
            $primaryColumn = new Column('id', 'integer', false, null, null, null, true, true);
        }
        
        if ($primaryColumn) {
            $this->addPrimary($primaryColumn);
        }
    }
    
    /**
     * add primary key(s) to table
     * @param mixed $primaryColumn
     * Column - if you want to define your own column (column is added to list of columns)
     * string - name of column in list of columns
     * array of strings - names of columns in list of columns
     * array of Column - list of own columns (all columns are added to list of columns)
     */
    public function addPrimary($primaryColumn)
    {
        if ($primaryColumn instanceof Column) {
            $this->columns[$primaryColumn->getName()] = $primaryColumn;
            $this->primaryColumns[] = $primaryColumn->getName();
        } elseif (is_string($primaryColumn)) {
            $this->primaryColumns[] = $primaryColumn;
        } elseif (is_array($primaryColumn)) {
            foreach ($primaryColumn as $column) {
                $this->addPrimary($column);
            }
        } else {
            throw new InvalidArgumentException('Unsupported type of primary column');
        }
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function addColumn(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $length = null,
        $decimals = null,
        $signed = true,
        $autoincrement = false
    ) {
        $this->columns[$name] = new Column($name, $type, $allowNull, $default, $length, $decimals, $signed, $autoincrement);
        return $this;
    }
    
    public function getColumns()
    {
        return $this->columns;
    }
    
    public function getColumn($name)
    {
        if (!isset($this->columns[$name])) {
            throw new Exception('Column "' . $name . '" not found');
        }
        return $this->columns[$name];
    }
    
    public function getPrimaryColumns()
    {
        return $this->primaryColumns;
    }
    
    public function addIndex($columns, $type = Index::TYPE_NORMAL, $method = Index::METHOD_DEFAULT)
    {
        $this->indexes[] = new Index($columns, $type, $method);
        return $this;
    }
    
    public function getIndexes()
    {
        return $this->indexes;
    }
    
    /**
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return Table
     */
    public function addForeignKey($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = ForeignKey::RESTRICT, $onUpdate = ForeignKey::RESTRICT)
    {
        $this->foreignKeys[] = new ForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate);
        return $this;
    }
    
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }
}
