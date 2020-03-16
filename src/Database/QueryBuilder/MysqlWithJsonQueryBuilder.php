<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;

class MysqlWithJsonQueryBuilder extends MysqlQueryBuilder
{
    protected function typeMap(): array
    {
        $typeMap = parent::typeMap();
        $typeMap[Column::TYPE_JSON] = 'json';
        return $typeMap;
    }
}
