<?php

namespace Phoenix\Database\Structure;

use Exception;

class Table
{
    private $name;

    private $charset;

    private $collation;

    private $columns = [];

    private $primaryColumns = [];

    private $foreignKeys = [];

    private $indexes = [];

    private $columnsToDrop = [];

    private $foreignKeysToDrop = [];

    private $indexesToDrop = [];

    private $columnsToChange = [];

    private $dropPrimaryKey = false;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * add primary key(s) to table
     * @param mixed $primaryColumn
     * true - if you want classic autoincrement integer primary column with name id
     * Column - if you want to define your own column (column is added to list of columns)
     * string - name of column in list of columns
     * array of strings - names of columns in list of columns
     * array of Column - list of own columns (all columns are added to list of columns)
     * other (false, null) - if your table doesn't have primary key
     */
    public function addPrimary($primaryColumn)
    {
        if ($primaryColumn === true) {
            $primaryColumn = new Column('id', 'integer', ['autoincrement' => true]);
            return $this->addPrimary($primaryColumn);
        }

        if ($primaryColumn instanceof Column) {
            $this->columns = array_merge([$primaryColumn->getName() => $primaryColumn], $this->columns);
            $this->primaryColumns = array_merge([$primaryColumn->getName() => $primaryColumn->getName()], $this->primaryColumns);
            return $this;
        }

        if (is_string($primaryColumn)) {
            $this->primaryColumns = array_merge([$primaryColumn => $primaryColumn], $this->primaryColumns);
            return $this;
        }

        if (is_array($primaryColumn)) {
            foreach (array_reverse($primaryColumn) as $column) {
                $this->addPrimary($column);
            }
        }
        return $this;
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
     * @param string $oldName
     * @param Column $newColumn
     * @return Table
     */
    public function changeColumn($oldName, Column $newColumn)
    {
        if (isset($this->columns[$oldName])) {
            $this->columns[$oldName] = $newColumn;
            return $this;
        }

        $this->columnsToChange[$oldName] = $newColumn;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumnsToChange()
    {
        return $this->columnsToChange;
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
     * @param string $indexName
     * @return Table
     */
    public function dropIndex($indexName)
    {
        $this->indexesToDrop[] = $indexName;
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
     * @param ForeignKey $foreignKey
     * @return Table
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $this->foreignKeys[] = $foreignKey;
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
     * @return Table
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

    /**
     * @return Table
     */
    public function dropPrimaryKey()
    {
        $this->dropPrimaryKey = true;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasPrimaryKeyToDrop()
    {
        return $this->dropPrimaryKey;
    }

    /**
     * @param string $charset
     * @return Table
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param string $collation
     * @return Table
     */
    public function setCollation($collation)
    {
        $this->collation = $collation;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollation()
    {
        return $this->collation;
    }
}
