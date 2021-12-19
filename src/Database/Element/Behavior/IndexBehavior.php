<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Exception\InvalidArgumentValueException;

trait IndexBehavior
{
    /** @var Index[] */
    private array $indexes = [];

    /** @var string[]  */
    private array $indexesToDrop = [];

    /**
     * @param string|string[]|IndexColumn|IndexColumn[] $columns name(s) of column(s) or IndexColumn instance(s)
     * @throws InvalidArgumentValueException if index type or index method is not allowed
     */
    public function addIndex($columns, string $type = Index::TYPE_NORMAL, string $method = Index::METHOD_DEFAULT, string $name = ''): MigrationTable
    {
        $columns = $this->createIndexColumns($columns);
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
     * @param string|string[]|IndexColumn|IndexColumn[] $columns
     * @return MigrationTable
     */
    public function dropIndex($columns): MigrationTable
    {
        $columns = $this->createIndexColumns($columns);
        $indexName = $this->createIndexName($columns);
        return $this->dropIndexByName($indexName);
    }

    public function dropIndexByName(string $indexName): MigrationTable
    {
        $this->indexesToDrop[] = $indexName;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getIndexesToDrop(): array
    {
        return $this->indexesToDrop;
    }

    /**
     * @param IndexColumn[] $columns
     * @param string $name
     * @return string
     */
    private function createIndexName(array $columns, string $name = ''): string
    {
        if ($name) {
            return $name;
        }

        $nameParts = [
            'idx',
            $this->getName(),
        ];
        foreach ($columns as $column) {
            $nameParts[] = $column->getName();
            $columnSettings = $column->getSettings()->getNonDefaultSettings();
            ksort($columnSettings);
            foreach ($columnSettings as $setting => $value) {
                $nameParts[] = substr($setting, 0, 1) . $value;
            }
        }
        return strtolower(implode('_', $nameParts));
    }

    /**
     * @param string|string[]|IndexColumn|IndexColumn[] $columns
     * @return IndexColumn[]
     */
    private function createIndexColumns($columns): array
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $columnList = [];
        foreach ($columns as $column) {
            $column = $column instanceof IndexColumn ? $column : new IndexColumn($column);
            $columnList[] = $column;
        }
        return $columnList;
    }

    abstract public function getName(): string;
}
