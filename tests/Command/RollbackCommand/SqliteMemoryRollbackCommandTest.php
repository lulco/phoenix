<?php

namespace Phoenix\Tests\Command\RollbackCommand;

class SqliteMemoryRollbackCommandTest extends RollbackCommandTest
{
    protected function getEnvironment()
    {
        return 'sqlite_memory';
    }
}
