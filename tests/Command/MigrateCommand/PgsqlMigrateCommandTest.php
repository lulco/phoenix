<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\MigrateCommand;

use Phoenix\Tests\Command\PgsqlCommandBehavior;

final class PgsqlMigrateCommandTest extends MigrateCommandTest
{
    use PgsqlCommandBehavior;
}
