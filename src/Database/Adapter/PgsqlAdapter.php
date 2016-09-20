<?php

namespace Phoenix\Database\Adapter;

use LogicException;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;

class PgsqlAdapter extends PdoAdapter
{
    /**
     * @return PgsqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new PgsqlQueryBuilder();
        }
        return $this->queryBuilder;
    }

    public function tableInfo($table)
    {
        throw new LogicException('Not yet implemented');
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? '{' . implode(',', $value) . '}' : $value;
    }
}
