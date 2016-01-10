<?php

namespace Phoenix\QueryBuilder;

interface QueryBuilderInterface
{
    /**
     * @param Table $table
     * @return string|array string if one query is needed for create table, array if more queries are needed
     */
    public function createTable(Table $table);

    /**
     * @param Table $table
     * @return string|array string if one query is needed for drop table, array if more queries are needed
     */
    public function dropTable(Table $table);
    
    /**
     * @param Table $table
     * @return string|array string if one query is needed for alter table, array if more queries are needed
     */
    public function alterTable(Table $table);
}
