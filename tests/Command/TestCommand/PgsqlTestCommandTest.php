<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\TestCommand;

use Phoenix\Tests\Command\PgsqlCommandBehavior;

final class PgsqlTestCommandTest extends TestCommandTest
{
    use PgsqlCommandBehavior;
}
