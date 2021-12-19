<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\RollbackCommand;

use Phoenix\Tests\Command\PgsqlCommandBehavior;

final class PgsqlRollbackCommandTest extends RollbackCommandTest
{
    use PgsqlCommandBehavior;
}
