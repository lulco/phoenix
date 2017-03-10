<?php

namespace Phoenix\Tests\Command\RollbackCommand;

class PgsqlRollbackCommandTest extends RollbackCommandTest
{
    protected function getEnvironment()
    {
        return 'pgsql';
    }
}
