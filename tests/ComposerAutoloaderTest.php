<?php

declare(strict_types=1);

namespace Phoenix\Tests;

use PHPUnit\Framework\TestCase;

final class ComposerAutoloaderTest extends TestCase
{
    public function testSuccessAutoload(): void
    {
        $autoload = require __DIR__ . '/../src/composer_autoloader.php';
        $this->assertTrue($autoload());
    }

    public function testAutoloadNotFound(): void
    {
        rename(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../vendor/_autoload.php');
        $autoload = require __DIR__ . '/../src/composer_autoloader.php';
        $this->assertFalse($autoload());
        rename(__DIR__ . '/../vendor/_autoload.php', __DIR__ . '/../vendor/autoload.php');
    }
}
