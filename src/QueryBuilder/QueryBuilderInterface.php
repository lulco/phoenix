<?php

namespace Phoenix\QueryBuilder;

interface QueryBuilderInterface
{
    public function createTable();
    
    public function dropTable();
    
//    public function alterTable();
}
