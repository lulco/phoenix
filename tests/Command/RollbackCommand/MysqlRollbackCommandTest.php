<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\RollbackCommand;

use Phoenix\Tests\Command\MysqlCommandBehavior;

final class MysqlRollbackCommandTest extends RollbackCommandTest
{
    use MysqlCommandBehavior;
}
