<?php

namespace Phoenix\Database\Element;

use Exception;
use InvalidArgumentException;

class Table
{
    private $name;
    
    private $columns = [];
    
    private $primaryColumns = [];
    
    private $foreignKeys = [];
    
    private $indexes = [];
    
    private $columnsToDrop = [];
    
    private $foreignKeysToDrop = [];
    
    private $indexesToDrop = [];
    
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
            $primaryColumn = new Column('id', 'integer', ['autoincrement' => true]);
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
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Column $column
     * @return Table
     */
    public function addColumn(Column $column)
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }
    
    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * @param string $name
     * @return Column
     * @throws Exception if column is not found
     */
    public function getColumn($name)
    {
        if (!isset($this->columns[$name])) {
            throw new Exception('Column "' . $name . '" not found');
        }
        return $this->columns[$name];
    }
    
    /**
     * @param string $name
     * @return Table
     */
    public function dropColumn($name)
    {
        $this->columnsToDrop[] = $name;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getColumnsToDrop()
    {
        return $this->columnsToDrop;
    }
    
    /**
     * @return array
     */
    public function getPrimaryColumns()
    {
        return $this->primaryColumns;
    }
    
    /**
     * @param Index $index
     * @return Table
     */
    public function addIndex(Index $index)
    {
        $this->indexes[] = $index;
        return $this;
    }
    
    /**
     * @return Index[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
    
    /**
     * @param string|array $columns
     * @return Table
     */
    public function dropIndex($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->indexesToDrop[] = implode('_', $columns);
        return $this;
    }
    
    /**
     * @return array
     */
    public function getIndexesToDrop()
    {
        return $this->indexesToDrop;
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
    
    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }
    
    /**
     * @param string|array $columns
     */
    public function dropForeignKey($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->foreignKeysToDrop[] = $this->getName() . '_' . implode('_', $columns);
        return $this;
    }

    /**
     * @return array
     */
    public function getForeignKeysToDrop()
    {
        return $this->foreignKeysToDrop;
    }
}
