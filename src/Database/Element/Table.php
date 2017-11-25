<?php

namespace Phoenix\Database\Element;

class Table
{
    private $name;

    private $charset;

    private $collation;

    private $comment;

    private $columns = [];

    private $primaryColumns;

    private $foreignKeys = [];

    private $indexes = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setName(string $name): Table
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCharset(?string $charset): Table
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCollation(?string $collation): Table
    {
        $this->collation = $collation;
        return $this;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function setComment(?string $comment): Table
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setPrimary(?array $primaryColumns = null): Table
    {
        $this->primaryColumns = $primaryColumns;
        return $this;
    }

    public function getPrimary(): ?array
    {
        return $this->primaryColumns;
    }

    public function addColumn(Column $column): Table
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ?Column
    {
        return isset($this->columns[$name]) ? $this->columns[$name] : null;
    }

    public function removeColumn(string $name): Table
    {
        unset($this->columns[$name]);
        return $this;
    }

    public function changeColumn(string $name, Column $column): Table
    {
        $this->removeColumn($name);
        $this->addColumn($column);
        return $this;
    }

    public function addIndex(Index $index): Table
    {
        $this->indexes[$index->getName()] = $index;
        return $this;
    }

    public function getIndex(string $name): ?Index
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function removeIndex(string $indexName): Table
    {
        unset($this->indexes[$indexName]);
        return $this;
    }

    public function addForeignKey(ForeignKey $foreignKey): Table
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
        return $this;
    }

    public function getForeignKey(string $name): ?ForeignKey
    {
        return isset($this->foreignKeys[$name]) ? $this->foreignKeys[$name] : null;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function removeForeignKey(string $foreignKeyName): Table
    {
        unset($this->foreignKeys[$foreignKeyName]);
        return $this;
    }
}
