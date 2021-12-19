<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Exception\InvalidArgumentValueException;

final class ForeignKey
{
    use ParamsCheckerBehavior;

    public const DEFAULT_ACTION = '';
    public const RESTRICT = 'RESTRICT';
    public const NO_ACTION = 'NO ACTION';
    public const CASCADE = 'CASCADE';
    public const SET_NULL = 'SET NULL';

    /** @var string[] */
    private array $columns = [];

    private string $referencedTable;

    /** @var string[] */
    private array $referencedColumns;

    private string $onDelete;

    private string $onUpdate;

    /**
     * ForeignKey constructor.
     * @param string[] $columns
     * @param string[] $referencedColumns
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
