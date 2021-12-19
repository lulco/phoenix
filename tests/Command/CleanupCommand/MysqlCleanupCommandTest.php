<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\CleanupCommand;

use Phoenix\Tests\Command\MysqlCommandBehavior;

final class MysqlCleanupCommandTest extends CleanupCommandTest
{
    use MysqlCommandBehavior;
}
