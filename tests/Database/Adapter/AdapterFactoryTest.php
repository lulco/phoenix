<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

class AdapterFactoryTest extends TestCase
{
    public function testMysql()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf(MysqlAdapter::class, AdapterFactory::instance($config));
    }

    public function testPgsql()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'pgsql',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf(PgsqlAdapter::class, AdapterFactory::instance($config));
    }

    public function testSqlite()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'sqlite',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf(SqliteAdapter::class, AdapterFactory::instance($config));
    }

    public function testUnknown()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'unknown',
            'dsn' => 'sqlite::memory:',
        ]);

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Unknown adapter "unknown". Use one of value: "mysql", "pgsql", "sqlite".');
        AdapterFactory::instance($config);
    }
}
