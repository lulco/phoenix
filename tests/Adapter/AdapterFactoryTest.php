<?php

namespace Phoenix\Tests;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactory;
use PHPUnit_Framework_TestCase;

class AdapterFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testMysql()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf('\Phoenix\Database\Adapter\MysqlAdapter', AdapterFactory::instance($config));
    }
    
    public function testPgsql()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'pgsql',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf('\Phoenix\Database\Adapter\PgsqlAdapter', AdapterFactory::instance($config));
    }
    
    public function testSqlite()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'sqlite',
            'dsn' => 'sqlite::memory:',
        ]);
        $this->assertInstanceOf('\Phoenix\Database\Adapter\SqliteAdapter', AdapterFactory::instance($config));
    }
    
    public function testUnknown()
    {
        $config = new EnvironmentConfig([
            'adapter' => 'unknown',
            'dsn' => 'sqlite::memory:',
        ]);
        
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Unknown adapter "unknown". Use one of value: "mysql", "pgsql", "sqlite".');
        AdapterFactory::instance($config);
    }
}
