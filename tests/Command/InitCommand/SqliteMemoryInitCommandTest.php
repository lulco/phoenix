<?php

namespace Phoenix\Tests\Command\InitCommand;

class SqliteMemoryInitCommandTest extends InitCommandTest
{
    protected function getEnvironment()
    {
        return 'sqlite_memory';
    }
}
