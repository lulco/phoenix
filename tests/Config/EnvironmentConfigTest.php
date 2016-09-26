<?php

namespace Phoenix\Tests\Config;

use Phoenix\Config\EnvironmentConfig;
use PHPUnit_Framework_TestCase;

class EnvironmentConfigTest extends PHPUnit_Framework_TestCase
{
    public function testAdapterAndConfiguredDsn()
    {
        $config = [
            'adapter' => 'test_adapter',
            'db_name' => 'test_db_name',
            'host' => 'test_host',
        ];
        $environmentConfig = new EnvironmentConfig($config);
        $this->assertEquals($config, $environmentConfig->getConfiguration());
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('test_adapter:dbname=test_db_name;host=test_host', $environmentConfig->getDsn());
        $this->assertNull($environmentConfig->getUsername());
        $this->assertNull($environmentConfig->getPassword());
    }
    
    public function testAdapterAndConfiguredDsnWithPortAndCharset()
    {
        $config = [
            'adapter' => 'test_adapter',
            'db_name' => 'test_db_name',
            'host' => 'test_host',
            'port' => 'port',
            'charset' => 'utf8',
        ];
        $environmentConfig = new EnvironmentConfig($config);
        $this->assertEquals($config, $environmentConfig->getConfiguration());
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('test_adapter:dbname=test_db_name;host=test_host;port=port;charset=utf8', $environmentConfig->getDsn());
        $this->assertNull($environmentConfig->getUsername());
        $this->assertNull($environmentConfig->getPassword());
    }
    
    public function testPgsqlAdapterAndConfiguredDsnWithCharset()
    {
        $environmentConfig = new EnvironmentConfig([
            'adapter' => 'pgsql',
            'db_name' => 'test_db_name',
            'host' => 'test_host',
            'charset' => 'utf8',
        ]);
        $this->assertEquals('pgsql', $environmentConfig->getAdapter());
        $this->assertEquals('pgsql:dbname=test_db_name;host=test_host;options=\'--client_encoding=utf8\'', $environmentConfig->getDsn());;
        $this->assertNull($environmentConfig->getUsername());
        $this->assertNull($environmentConfig->getPassword());
    }
    
    public function testCustomDsn()
    {
        $environmentConfig = new EnvironmentConfig([
            'adapter' => 'test_adapter',
            'dsn' => 'custom_dsn',
        ]);
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('custom_dsn', $environmentConfig->getDsn());
        $this->assertNull($environmentConfig->getUsername());
        $this->assertNull($environmentConfig->getPassword());
    }
    
    public function testUsernameAndPassword()
    {
        $environmentConfig = new EnvironmentConfig([
            'adapter' => 'test_adapter',
            'db_name' => 'test_db_name',
            'host' => 'test_host',
            'username' => 'test_username',
            'password' => 'test_password',
        ]);
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('test_adapter:dbname=test_db_name;host=test_host', $environmentConfig->getDsn());
        $this->assertEquals('test_username', $environmentConfig->getUsername());
        $this->assertEquals('test_password', $environmentConfig->getPassword());
    }
    
    public function testUsernameAndPasswordWithCustomDsn()
    {
        $environmentConfig = new EnvironmentConfig([
            'adapter' => 'test_adapter',
            'dsn' => 'custom_dsn',
            'username' => 'test_username',
            'password' => 'test_password',
        ]);
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('custom_dsn', $environmentConfig->getDsn());
        $this->assertEquals('test_username', $environmentConfig->getUsername());
        $this->assertEquals('test_password', $environmentConfig->getPassword());
    }
}
