<?php

namespace Phoenix\QueryBuilder;

interface QueryBuilderInterface
{
    /**
     * @param Table $table
     * @return string query for create table
     */
    public function createTable(Table $table);

    /**
     * @param Table $table
     * @return string query for drop table
     */
    public function dropTable(Table $table);
    
//    public function alterTable();
}
