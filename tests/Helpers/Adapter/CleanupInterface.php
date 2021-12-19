<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Adapter;

interface CleanupInterface
{
    public function cleanupDatabase(): void;
}
