<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Database\QueryBuilder\MysqlWithJsonQueryBuilder;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class AdapterFactoryTest extends TestCase
{
    public function testMysql(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $this->assertInstanceOf(MysqlQueryBuilder::class, $adapter->getQueryBuilder());
    }

    public function testMysqlVersion550(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'version' => '5.5.0',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $this->assertInstanceOf(MysqlQueryBuilder::class, $adapter->getQueryBuilder());
    }

    public function testMysqlVersion508(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'version' => '5.0.8',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $queryBuilder = $adapter->getQueryBuilder();
        $this->assertInstanceOf(MysqlQueryBuilder::class, $queryBuilder);
        $this->assertNotInstanceOf(MysqlWithJsonQueryBuilder::class, $queryBuilder);
    }

    public function testMysqlVersion578(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'version' => '5.7.8',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $this->assertInstanceOf(MysqlWithJsonQueryBuilder::class, $adapter->getQueryBuilder());
    }

    public function testMysqlVersion8019(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'version' => '8.0.19',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $this->assertInstanceOf(MysqlWithJsonQueryBuilder::class, $adapter->getQueryBuilder());
    }

    public function testPgsql(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'pgsql',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(PgsqlAdapter::class, $adapter);
    }

    public function testUnknown(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'unknown',
            'dsn' => 'sqlite::memory:',
        ]);

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Unknown adapter "unknown". Use one of value: "mysql", "pgsql".');
        AdapterFactory::instance($config);
    }
}
