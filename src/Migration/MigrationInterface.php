<?php

namespace Phoenix\Migration;

use Phoenix\Database\Element\Structure;

/**
 * Every migration must implement this interface
 *
 * @package Phoenix\Migration
 */
interface MigrationInterface
{
    /**
     * Gets date-time for migration
     *
     * @return string
     */
    public function getDatetime(): string;

    /**
     * Gets class name without extra \
     *
     * @return string
     */
    public function getClassName(): string;

    /**
     * Gets class name with all characters
     *
     * @return string
     */
    public function getFullClassName(): string;

    /**
     * Execute this migration to never version
     *
     * @param bool $dry Run migration as dry?
     *
     * @return array
     */
    public function migrate(bool $dry = false): array;

    /**
     * Execute this migration to older version?
     *
     * @param bool $dry Run migration as dry?
     *
     * @return array
     */
    public function rollback(bool $dry = false): array;

    /**
     * Updates table structure
     *
     * @param Structure $structure
     */
    public function updateStructure(Structure $structure): void;

    /**
     * Gets executed queries
     *
     * @return array
     */
    public function getExecutedQueries(): array;
}
