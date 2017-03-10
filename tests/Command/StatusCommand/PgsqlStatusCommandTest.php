<?php

namespace Phoenix\Tests\Command\StatusCommand;

class PgsqlStatusCommandTest extends StatusCommandTest
{
    protected function getEnvironment()
    {
        return 'pgsql';
    }
}
