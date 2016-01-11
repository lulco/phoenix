<?php

namespace Phoenix\Tests;

use Phoenix\Config\EnvironmentConfig;
use PHPUnit_Framework_TestCase;

class EnvironmentConfigTest extends PHPUnit_Framework_TestCase
{
    public function testAdapterAndConfiguredDsn()
    {
        $environmentConfig = new EnvironmentConfig([
            'adapter' => 'test_adapter',
            'db_name' => 'test_db_name',
            'host' => 'test_host',
        ]);
        $this->assertEquals('test_adapter', $environmentConfig->getAdapter());
        $this->assertEquals('test_adapter:dbname=test_db_name;host=test_host', $environmentConfig->getDsn());
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
