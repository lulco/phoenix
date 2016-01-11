<?php

namespace Phoenix\Tests;

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
