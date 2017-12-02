<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\MigrationTable;

interface QueryBuilderInterface
{
    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function createTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function dropTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function renameTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function alterTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array list of queries
     */
    public function copyTable(MigrationTable $table): array;

    /**
     * @param string $string
     * @return string escaped string
     */
    public function escapeString(string $string): string;
}
