<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Database\Element\Behavior\AutoIncrementBehavior;

final class Table
{
    use AutoIncrementBehavior;

    private string $name;

    private ?string $charset = null;

    private ?string $collation = null;

    private ?string $comment = null;

    /** @var Column[] */
    private array $columns = [];

    /** @var string[] */
    private array $primaryColumns = [];

    /** @var ForeignKey[] */
    private array $foreignKeys = [];

    /** @var UniqueConstraint[] */
    private array $uniqueConstraints = [];

    /** @var array<string, Index> */
    private array $indexes = [];

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

    /**
     * @param string[] $primaryColumns
     * @return Table
     */
    public function setPrimary(array $primaryColumns): Table
    {
        $this->primaryColumns = $primaryColumns;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getPrimary(): array
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

    /**
     * @return UniqueConstraint[]
     */
    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    public function addUniqueConstraint(UniqueConstraint $uniqueConstraint): Table
    {
        $this->uniqueConstraints[$uniqueConstraint->getName()] = $uniqueConstraint;

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

    public function removeUniqueConstraint(string $uniqueConstraintName): Table
    {
        unset($this->uniqueConstraints[$uniqueConstraintName]);

        return $this;
    }

    public function toMigrationTable(): MigrationTable
    {
        $table = clone $this;

        $migrationTable = new MigrationTable($table->getName(), $table->getPrimary());
        $migrationTable->setAutoIncrement($table->getAutoIncrement());
        $migrationTable->setCharset($table->getCharset());
        $migrationTable->setCollation($table->getCollation());
        $migrationTable->setComment($table->getComment());

        foreach ($table->getColumns() as $column) {
            $migrationTable->addColumn($column->getName(), $column->getType(), $column->getSettings()->getSettings());
        }
        foreach ($table->getIndexes() as $index) {
            $migrationTable->addIndex($index->getColumns(), $index->getType(), $index->getMethod(), $index->getName());
        }
        foreach ($table->getForeignKeys() as $foreignKey) {
            $migrationTable->addForeignKey($foreignKey->getColumns(), $foreignKey->getReferencedTable(), $foreignKey->getReferencedColumns(), $foreignKey->getOnDelete(), $foreignKey->getOnUpdate());
        }
        foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
            $migrationTable->addUniqueConstraint($uniqueConstraint->getColumns(), $uniqueConstraint->getName());
        }

        return $migrationTable;
    }
}
