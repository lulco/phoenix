<?php

namespace Phoenix\Database\Adapter;

use LogicException;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;

class MysqlAdapter extends PdoAdapter
{
    /**
     * @return MysqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new MysqlQueryBuilder();
        }
        return $this->queryBuilder;
    }

    protected function loadStructure()
    {
        return new \Phoenix\Database\Element\Structure();
    }

    public function tableInfo($table)
    {
        throw new LogicException('Not yet implemented');
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

}
