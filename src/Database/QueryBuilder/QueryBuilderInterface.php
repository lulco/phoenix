<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Table;

interface QueryBuilderInterface
{
    /**
     * @param Table $table
     * @return array list of queries
     */
    public function createTable(Table $table);

    /**
     * @param Table $table
     * @return array list of queries
     */
    public function dropTable(Table $table);
    
    /**
     * @param Table $table
     * @return array list of queries
     */
    public function alterTable(Table $table);
    
    /**
     * @param string $string
     * @return string escaped string
     */
    public function escapeString($string);
    
}
