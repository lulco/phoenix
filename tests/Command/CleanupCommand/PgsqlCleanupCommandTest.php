<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\CleanupCommand;

use Phoenix\Tests\Command\PgsqlCommandBehavior;

final class PgsqlCleanupCommandTest extends CleanupCommandTest
{
    use PgsqlCommandBehavior;
}
