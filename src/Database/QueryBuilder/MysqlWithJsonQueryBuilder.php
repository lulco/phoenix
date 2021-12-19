<?php

declare(strict_types=1);

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;

final class MysqlWithJsonQueryBuilder extends MysqlQueryBuilder
{
    protected function typeMap(): array
    {
        $typeMap = parent::typeMap();
        $typeMap[Column::TYPE_JSON] = 'json';
        return $typeMap;
    }
}
