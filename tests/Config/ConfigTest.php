<?php

namespace Phoenix\Tests\Config;

use Phoenix\Config\Config;
use PHPUnit_Framework_TestCase;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $config = new Config([
            'migration_dirs' => [
                'first_dir',
                'second_dir'
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);
        $this->assertEquals('phoenix_log', $config->getLogTableName());
        $this->assertCount(2, $config->getMigrationDirs());
        $this->assertEquals('first', $config->getDefaultEnvironment());
        $this->assertInstanceOf('\Phoenix\Config\EnvironmentConfig', $config->getEnvironmentConfig('first'));
        $this->assertInstanceOf('\Phoenix\Config\EnvironmentConfig', $config->getEnvironmentConfig('second'));
    }
    
    public function testOverridenDefaults()
    {
        $config = new Config([
            'default_environment' => 'second',
            'log_table_name' => 'custom_log_table_name',
            'migration_dirs' => [
                'first_dir',
                'second_dir'
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);
        $this->assertEquals('custom_log_table_name', $config->getLogTableName());
        $this->assertCount(2, $config->getMigrationDirs());
        $this->assertEquals('second', $config->getDefaultEnvironment());
        $this->assertInstanceOf('\Phoenix\Config\EnvironmentConfig', $config->getEnvironmentConfig('first'));
        $this->assertInstanceOf('\Phoenix\Config\EnvironmentConfig', $config->getEnvironmentConfig('second'));
    }
    
    public function testEmptyMigrationDirs()
    {
        $this->setExpectedException('\Phoenix\Exception\ConfigException', 'Empty migration dirs');
        $config = new Config([
            'environments' => [
                'first' => [],
            ],
        ]);
    }
    
    public function testSelectMigrationDir()
    {
        $config = new Config([
            'default_environment' => 'second',
            'log_table_name' => 'custom_log_table_name',
            'migration_dirs' => [
                'first_dir',
                'second_dir'
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);
        
        $this->assertEquals('first_dir', $config->getMigrationDir());
        $this->assertEquals('first_dir', $config->getMigrationDir(0));
        $this->assertEquals('second_dir', $config->getMigrationDir(1));
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Directory "xxx" doesn\'t exist. Use: 0, 1');
        $config->getMigrationDir('xxx');
    }
    
    public function testSelectNamedMigrationDir()
    {
        $config = new Config([
            'default_environment' => 'second',
            'log_table_name' => 'custom_log_table_name',
            'migration_dirs' => [
                'first' => 'first_dir',
                'second' => 'second_dir'
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);
        
        $this->assertEquals('first_dir', $config->getMigrationDir());
        $this->assertEquals('first_dir', $config->getMigrationDir('first'));
        $this->assertEquals('second_dir', $config->getMigrationDir('second'));
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Directory "xxx" doesn\'t exist. Use: first, second');
        $config->getMigrationDir('xxx');
    }
    
    public function testEmptyEnvironments()
    {
        $this->setExpectedException('\Phoenix\Exception\ConfigException', 'Empty environments');
        $config = new Config([
            'migration_dirs' => [
                'first_dir'
            ],
        ]);
    }
}
