<?php

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

class ForeignKey
{
    use ParamsCheckerBehavior;

    const DEFAULT_ACTION = '';
    const RESTRICT = 'RESTRICT';
    const NO_ACTION = 'NO ACTION';
    const CASCADE = 'CASCADE';
    const SET_NULL = 'SET NULL';

    /** @var string[] */
    private $columns = [];

    /** @var string */
    private $referencedTable;

    /** @var string[] */
    private $referencedColumns;

    /** @var string */
    private $onDelete;

    /** @var string */
    private $onUpdate;

    /**
     * ForeignKey constructor.
     * @param string[] $columns
     * @param string $referencedTable
     * @param string[] $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @throws InvalidArgumentValueException if onDelete action or onUpdate action is not allowed
     */
    public function __construct(array $columns, string $referencedTable, array $referencedColumns = ['id'], string $onDelete = self::DEFAULT_ACTION, string $onUpdate = self::DEFAULT_ACTION)
    {
        $this->columns = $columns;
        $this->referencedTable = $referencedTable;
        $this->referencedColumns = $referencedColumns;

        $this->onDelete = strtoupper($onDelete);
        $this->inArray($this->onDelete, [self::DEFAULT_ACTION, self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL], 'Action "' . $onDelete . '" is not allowed on delete');

        $this->onUpdate = strtoupper($onUpdate);
        $this->inArray($this->onUpdate, [self::DEFAULT_ACTION, self::RESTRICT, self::NO_ACTION, self::CASCADE, self::SET_NULL], 'Action "' . $onUpdate . '" is not allowed on update');
    }

    public function getName(): string
    {
        return implode('_', $this->columns);
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setReferencedTable(string $referencedTable): ForeignKey
    {
        $this->referencedTable = $referencedTable;
        return $this;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    /**
     * @return string[]
     */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }
}
