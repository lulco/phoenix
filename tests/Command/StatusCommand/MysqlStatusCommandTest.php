<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\StatusCommand;

use Phoenix\Tests\Command\MysqlCommandBehavior;

final class MysqlStatusCommandTest extends StatusCommandTest
{
    use MysqlCommandBehavior;
}
