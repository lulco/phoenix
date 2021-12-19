<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\StatusCommand;

use Phoenix\Tests\Command\PgsqlCommandBehavior;

final class PgsqlStatusCommandTest extends StatusCommandTest
{
    use PgsqlCommandBehavior;
}
