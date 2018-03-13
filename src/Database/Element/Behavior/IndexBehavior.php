<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;

trait IndexBehavior
{
    private $indexes = [];

    private $indexesToDrop = [];

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

    private function createIndexName(array $columns, string $name = ''): string
    {
        if ($name) {
            return $name;
        }

        return 'idx_' . $this->getName() . '_' . implode('_', $columns);
    }

    abstract public function getName();
}
