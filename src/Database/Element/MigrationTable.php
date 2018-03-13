<?php

namespace Phoenix\Database\Element;

use Phoenix\Database\Element\Behavior\CopyTableBehavior;
use Phoenix\Database\Element\Behavior\ForeignKeyBehavior;
use Phoenix\Database\Element\Behavior\IndexBehavior;
use Phoenix\Database\Element\Behavior\PrimaryColumnsBehavior;

class MigrationTable
{
    use CopyTableBehavior;
    use ForeignKeyBehavior;
    use IndexBehavior;
    use PrimaryColumnsBehavior;

    const ACTION_CREATE = 'create';

    const ACTION_ALTER = 'alter';

    const ACTION_RENAME = 'rename';

    const ACTION_DROP = 'drop';

    const ACTION_COPY = 'copy';

    const COPY_ONLY_STRUCTURE = 'only_structure';

    const COPY_ONLY_DATA = 'only_data';

    const COPY_STRUCTURE_AND_DATA = 'structure_and_data';

    private $action = self::ACTION_ALTER;

    private $tmpPrimaryKey;

    private $name;

    private $newName;

    private $charset;

    private $collation;

    private $comment;

    private $columns = [];

    private $primaryColumnNames = [];

    private $columnsToDrop = [];

    private $columnsToChange = [];

    private $dropPrimaryKey = false;

    /**
     * @param mixed $primaryKey @see addPrimary()
     */
    public function __construct(string $name, $primaryKey = true)
    {
        $this->name = $name;
        $this->tmpPrimaryKey = $primaryKey;
    }

    public function addColumn(string $name, string $type, array $settings = []): MigrationTable
    {
        $column = new Column($name, $type, $settings);
        $this->columns[$column->getName()] = $column;
        return $this;
    }

    public function changeColumn(string $oldName, string $newName, string $type, array $settings = []): MigrationTable
    {
        $newColumn = new Column($newName, $type, $settings);
        if (isset($this->columns[$oldName])) {
            $this->columns[$oldName] = $newColumn;
            return $this;
        }

        $this->columnsToChange[$oldName] = $newColumn;
        return $this;
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
    public function addPrimary($primaryColumn): MigrationTable
    {
        if ($primaryColumn === true) {
            $primaryColumn = new Column('id', Column::TYPE_INTEGER, [ColumnSettings::SETTING_AUTOINCREMENT => true]);
            return $this->addPrimary($primaryColumn);
        }

        if ($primaryColumn instanceof Column) {
            $this->columns = array_merge([$primaryColumn->getName() => $primaryColumn], $this->columns);
            $this->primaryColumnNames = array_merge([$primaryColumn->getName()], $this->primaryColumnNames);
            return $this;
        }

        if (is_string($primaryColumn)) {
            $this->primaryColumnNames = array_merge([$primaryColumn], $this->primaryColumnNames);
            return $this;
        }

        if (is_array($primaryColumn)) {
            foreach (array_reverse($primaryColumn) as $column) {
                $this->addPrimary($column);
            }
        }
        return $this;
    }

    public function setName(string $name): MigrationTable
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNewName(): ?string
    {
        return $this->newName;
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

    public function dropColumn(string $name): MigrationTable
    {
        $this->columnsToDrop[] = $name;
        return $this;
    }

    public function getColumnsToDrop(): array
    {
        return $this->columnsToDrop;
    }

    /**
     * @return Column[]
     */
    public function getColumnsToChange(): array
    {
        return $this->columnsToChange;
    }

    public function getPrimaryColumnNames(): array
    {
        return $this->primaryColumnNames;
    }

    public function dropPrimaryKey(): MigrationTable
    {
        $this->dropPrimaryKey = true;
        return $this;
    }

    public function hasPrimaryKeyToDrop(): bool
    {
        return $this->dropPrimaryKey;
    }

    public function setCharset(?string $charset): MigrationTable
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCollation(?string $collation): MigrationTable
    {
        $this->collation = $collation;
        return $this;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function setComment(?string $comment): MigrationTable
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function unsetComment(): MigrationTable
    {
        return $this->setComment('');
    }

    public function create(): void
    {
        $this->action = self::ACTION_CREATE;
        $this->addPrimary($this->tmpPrimaryKey);
    }

    public function save(): void
    {
        $this->action = self::ACTION_ALTER;
    }

    public function drop(): void
    {
        $this->action = self::ACTION_DROP;
    }

    public function rename(string $newName): void
    {
        $this->action = self::ACTION_RENAME;
        $this->newName = $newName;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function toTable(): Table
    {
        $table = new Table($this->getName());
        $table->setCharset($this->getCharset());
        $table->setCollation($this->getCollation());
        $table->setComment($this->getComment());
        if ($this->getPrimaryColumnNames()) {
            $table->setPrimary($this->getPrimaryColumnNames());
        }
        foreach ($this->getColumns() as $column) {
            $table->addColumn($column);
        }
        foreach ($this->getIndexes() as $index) {
            $table->addIndex($index);
        }
        foreach ($this->getForeignKeys() as $foreignKey) {
            $table->addForeignKey($foreignKey);
        }
        return $table;
    }
}
