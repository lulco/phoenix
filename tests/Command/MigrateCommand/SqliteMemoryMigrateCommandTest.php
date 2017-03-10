<?php

namespace Phoenix\Tests\Command\MigrateCommand;

class SqliteMemoryMigrateCommandTest extends MigrateCommandTest
{
    protected function getEnvironment()
    {
        return 'sqlite_memory';
    }
}
