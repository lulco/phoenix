<?php

namespace Phoenix\Tests\Command\MigrateCommand;

class MysqlMigrateCommandTest extends MigrateCommandTest
{
    protected function getEnvironment()
    {
        return 'mysql';
    }
}
