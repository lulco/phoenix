<?php

namespace Phoenix\Migration;

use Phoenix\Database\Element\Structure;

interface MigrationInterface
{
    public function getDatetime(): string;

    public function getClassName(): string;

    public function getFullClassName(): string;

    public function migrate(bool $dry = false): array;

    public function rollback(bool $dry = false): array;

    public function updateStructure(Structure $structure): void;

    public function getExecutedQueries(): array;
}