<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Database\Element\Behavior\AutoIncrementBehavior;
use Phoenix\Database\Element\Behavior\CharsetAndCollationBehavior;
use Phoenix\Database\Element\Behavior\ColumnsToChangeBehavior;
use Phoenix\Database\Element\Behavior\ColumnsToDropBehavior;
use Phoenix\Database\Element\Behavior\ColumnsToRenameBehavior;
use Phoenix\Database\Element\Behavior\CommentBehavior;
use Phoenix\Database\Element\Behavior\CopyTableBehavior;
use Phoenix\Database\Element\Behavior\DropPrimaryKeyBehavior;
use Phoenix\Database\Element\Behavior\ForeignKeyBehavior;
use Phoenix\Database\Element\Behavior\IndexBehavior;
use Phoenix\Database\Element\Behavior\PrimaryColumnsBehavior;
use Phoenix\Database\Element\Behavior\UniqueConstraintBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

final class MigrationTable
{
    use AutoIncrementBehavior;
    use CharsetAndCollationBehavior;
    use ColumnsToChangeBehavior;
    use ColumnsToDropBehavior;
    use ColumnsToRenameBehavior;
    use CommentBehavior;
    use CopyTableBehavior;
    use DropPrimaryKeyBehavior;
    use ForeignKeyBehavior;
    use IndexBehavior;
    use PrimaryColumnsBehavior;
    use UniqueConstraintBehavior;

    public const ACTION_CREATE = 'create';

    public const ACTION_ALTER = 'alter';

    public const ACTION_RENAME = 'rename';

    public const ACTION_DROP = 'drop';

    public const ACTION_COPY = 'copy';

    public const ACTION_TRUNCATE = 'truncate';

    public const COPY_ONLY_STRUCTURE = 'only_structure';

    public const COPY_ONLY_DATA = 'only_data';

    public const COPY_STRUCTURE_AND_DATA = 'structure_and_data';

    private string $action = self::ACTION_ALTER;

    /** @var mixed */
    private $tmpPrimaryKey;

    private string $name;

    private ?string $newName = null;

    /** @var Column[] */
    private array $columns = [];

    /** @var string[] */
    private array $primaryColumnNames = [];

    /**
     * @param mixed $primaryKey @see addPrimary()
     */
    public function __construct(string $name, $primaryKey = true)
    {
        $this->name = $name;
        $this->tmpPrimaryKey = $primaryKey;
    }

    /**
     * @param array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string} $settings
     * @throws InvalidArgumentValueException
     */
    public function addColumn(string $name, string $type, array $settings = []): MigrationTable
    {
        $column = new Column($name, $type, $settings);
        $this->columns[$column->getName()] = $column;
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

    /**
     * @return string[]
     */
    public function getPrimaryColumnNames(): array
    {
        return $this->primaryColumnNames;
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

    public function truncate(): void
    {
        $this->action = self::ACTION_TRUNCATE;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function toTable(): Table
    {
        $table = new Table($this->getName());
        $table->setAutoIncrement($this->getAutoIncrement());
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
        foreach ($this->getUniqueConstraints() as $uniqueConstraint) {
            $table->addUniqueConstraint($uniqueConstraint);
        }

        return $table;
    }
}
