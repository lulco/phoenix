<?php

namespace Phoenix\Database\Element;

class Table
{
    private $name;

    private $charset;

    private $collation;

    private $columns = [];

    private $primaryColumns;

    private $foreignKeys = [];

    private $indexes = [];

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @return Table
     */
    public function setName($name)
    {
        $this->name = $name;
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

    /**
     * set primary key(s) to table
     * @param array|null $primaryColumns
     */
    public function setPrimary(array $primaryColumns = null)
    {
        $this->primaryColumns = $primaryColumns;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getPrimary()
    {
        return $this->primaryColumns;
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
     * @return Column|null
     */
    public function getColumn($name)
    {
        return isset($this->columns[$name]) ? $this->columns[$name] : null;
    }

    /**
     * @param string $name
     * @return Table
     */
    public function removeColumn($name)
    {
        unset($this->columns[$name]);
        return $this;
    }

    /**
     * @param string $name
     * @param Column $column
     * @return Table
     */
    public function changeColumn($name, Column $column)
    {
        $this->removeColumn($name);
        $this->addColumn($column);
        return $this;
    }

    /**
     * @param Index $index
     * @return Table
     */
    public function addIndex(Index $index)
    {
        $this->indexes[$index->getName()] = $index;
        return $this;
    }

    /**
     * @param string $name
     * @return Index|null
     */
    public function getIndex($name)
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
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
    public function removeIndex($indexName)
    {
        unset($this->indexes[$indexName]);
        return $this;
    }

    /**
     * @param ForeignKey $foreignKey
     * @return Table
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
        return $this;
    }

    /**
     * @param string $name
     * @return ForeignKey|null
     */
    public function getForeignKey($name)
    {
        return isset($this->foreignKeys[$name]) ? $this->foreignKeys[$name] : null;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * @param string $foreignKeyName
     * @return Table
     */
    public function removeForeignKey($foreignKeyName)
    {
        unset($this->foreignKeys[$foreignKeyName]);
        return $this;
    }
}
