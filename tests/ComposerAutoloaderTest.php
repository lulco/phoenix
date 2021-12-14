<?php

namespace Phoenix\Tests;

use PHPUnit\Framework\TestCase;

class ComposerAutoloaderTest extends TestCase
{
    public function testSuccessAutoload()
    {
        $autoload = require __DIR__ . '/../src/composer_autoloader.php';
        $this->assertTrue($autoload());
    }

    public function testAutoloadNotFound()
    {
        rename(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../vendor/_autoload.php');
        $autoload = require __DIR__ . '/../src/composer_autoloader.php';
        $this->assertFalse($autoload());
        rename(__DIR__ . '/../vendor/_autoload.php', __DIR__ . '/../vendor/autoload.php');
    }
}
