<?php

namespace Phoenix\Database\QueryBuilder;

use PDOStatement;
use Phoenix\Database\Element\MigrationTable;

interface QueryBuilderInterface
{
    /**
     * @param MigrationTable $table
     * @return string[] list of queries
     */
    public function createTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return string[] list of queries
     */
    public function dropTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return string[] list of queries
     */
    public function renameTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array<string|PDOStatement> list of queries
     */
    public function alterTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return array<string|PDOStatement> list of queries
     */
    public function copyTable(MigrationTable $table): array;

    /**
     * @param MigrationTable $table
     * @return string[] list of queries
     */
    public function truncateTable(MigrationTable $table): array;

    /**
     * @param string|null $string
     * @return string escaped string
     */
    public function escapeString(?string $string): string;
}
