<?php

namespace Phoenix\Tests\Command\StatusCommand;

class SqliteMemoryStatusCommandTest extends StatusCommandTest
{
    protected function getEnvironment()
    {
        return 'sqlite_memory';
    }
}
