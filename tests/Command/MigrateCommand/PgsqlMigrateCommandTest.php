<?php

namespace Phoenix\Tests\Command\MigrateCommand;

class PgsqlMigrateCommandTest extends MigrateCommandTest
{
    protected function getEnvironment()
    {
        return 'pgsql';
    }
}
