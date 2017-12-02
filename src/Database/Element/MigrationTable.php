<?php

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;

class MigrationTable
{
    use ParamsCheckerBehavior;

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

    private $primaryColumns = [];

    private $foreignKeys = [];

    private $indexes = [];

    private $columnsToDrop = [];

    private $foreignKeysToDrop = [];

    private $indexesToDrop = [];

    private $columnsToChange = [];

    private $dropPrimaryKey = false;

    private $copyType;

    /**
     * @param mixed $primaryKey
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
            $this->primaryColumns = array_merge([$primaryColumn->getName()], $this->primaryColumns);
            return $this;
        }

        if (is_string($primaryColumn)) {
            $this->primaryColumns = array_merge([$primaryColumn], $this->primaryColumns);
            return $this;
        }

        if (is_array($primaryColumn)) {
            foreach (array_reverse($primaryColumn) as $column) {
                $this->addPrimary($column);
            }
        }
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

    public function getPrimaryColumns(): array
    {
        return $this->primaryColumns;
    }

    /**
     * @param string|array $columns name(s) of column(s)
     * @param string $type type of index (unique, fulltext) default ''
     * @param string $method method of index (btree, hash) default ''
     * @param string $name name of index
     * @return MigrationTable
     */
    public function addIndex($columns, string $type = Index::TYPE_NORMAL, string $method = Index::METHOD_DEFAULT, string $name = ''): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $index = new Index($columns, $this->createIndexName($columns, $name), $type, $method);
        $this->indexes[] = $index;
        return $this;
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param string|array $columns
     */
    public function dropIndex($columns): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $indexName = $this->createIndexName($columns);
        return $this->dropIndexByName($indexName);
    }

    public function dropIndexByName(string $indexName): MigrationTable
    {
        $this->indexesToDrop[] = $indexName;
        return $this;
    }

    public function getIndexesToDrop(): array
    {
        return $this->indexesToDrop;
    }

    /**
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return MigrationTable
     */
    public function addForeignKey($columns, string $referencedTable, $referencedColumns = ['id'], string $onDelete = ForeignKey::DEFAULT_ACTION, string $onUpdate = ForeignKey::DEFAULT_ACTION): MigrationTable
    {
        $this->foreignKeys[] = new ForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate);
        return $this;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @param string|array $columns
     */
    public function dropForeignKey($columns): MigrationTable
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->foreignKeysToDrop[] = implode('_', $columns);
        return $this;
    }

    public function getForeignKeysToDrop(): array
    {
        return $this->foreignKeysToDrop;
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

    public function getCopyType(): string
    {
        return $this->copyType;
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

    public function copy(string $newName, string $copyType = self::COPY_ONLY_STRUCTURE): void
    {
        $this->inArray($copyType, [self::COPY_ONLY_STRUCTURE, self::COPY_ONLY_DATA, self::COPY_STRUCTURE_AND_DATA], 'Copy type "' . $copyType . '" is not allowed');

        $this->action = self::ACTION_COPY;
        $this->newName = $newName;
        $this->copyType = $copyType;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    private function createIndexName(array $columns, string $name = ''): string
    {
        if ($name) {
            return $name;
        }

        return 'idx_' . $this->getName() . '_' . implode('_', $columns);
    }

    public function toTable(): Table
    {
        $table = new Table($this->getName());
        $table->setCharset($this->getCharset());
        $table->setCollation($this->getCollation());
        $table->setComment($this->getComment());
        if ($this->getPrimaryColumns()) {
            $table->setPrimary($this->getPrimaryColumns());
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
