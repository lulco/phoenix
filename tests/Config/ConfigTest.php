<?php

namespace Phoenix\Tests\Config;

use Phoenix\Config\Config;
use Phoenix\Config\EnvironmentConfig;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\InvalidArgumentValueException;
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
                'third' => [],
            ],
        ]);
        $this->assertEquals('phoenix_log', $config->getLogTableName());
        $this->assertCount(2, $config->getMigrationDirs());
        $this->assertEquals('first', $config->getDefaultEnvironment());
        $this->assertInstanceOf(EnvironmentConfig::class, $config->getEnvironmentConfig('first'));
        $this->assertInstanceOf(EnvironmentConfig::class, $config->getEnvironmentConfig('second'));
        $this->assertTrue(is_array($config->getConfiguration()));
        $this->assertArrayHasKey('migration_dirs', $config->getConfiguration());
        $this->assertCount(2, $config->getConfiguration()['migration_dirs']);
        $this->assertCount(3, $config->getConfiguration()['environments']);
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
        $this->assertInstanceOf(EnvironmentConfig::class, $config->getEnvironmentConfig('first'));
        $this->assertInstanceOf(EnvironmentConfig::class, $config->getEnvironmentConfig('second'));
    }

    public function testEmptyMigrationDirs()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Empty migration dirs');
        new Config([
            'environments' => [
                'first' => [],
            ],
        ]);
    }

    public function testSelectTheOnlyMigrationDir()
    {
        $config = new Config([
            'default_environment' => 'second',
            'log_table_name' => 'custom_log_table_name',
            'migration_dirs' => [
                'first_dir',
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);
        $this->assertEquals('first_dir', $config->getMigrationDir());
        $this->assertEquals('first_dir', $config->getMigrationDir(0));
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Directory "xxx" doesn\'t exist. Use: 0');
        $config->getMigrationDir('xxx');
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

        $this->assertEquals('first_dir', $config->getMigrationDir(0));
        $this->assertEquals('second_dir', $config->getMigrationDir(1));
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('There are more then 1 migration dirs. Use one of them: 0, 1');
        $config->getMigrationDir();
    }

    public function testSelectTheOnlyNamedMigrationDir()
    {
        $config = new Config([
            'default_environment' => 'second',
            'log_table_name' => 'custom_log_table_name',
            'migration_dirs' => [
                'first' => 'first_dir',
            ],
            'environments' => [
                'first' => [],
                'second' => [],
            ],
        ]);

        $this->assertEquals('first_dir', $config->getMigrationDir());
        $this->assertEquals('first_dir', $config->getMigrationDir('first'));
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Directory "xxx" doesn\'t exist. Use: first');
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

        $this->assertEquals('first_dir', $config->getMigrationDir('first'));
        $this->assertEquals('second_dir', $config->getMigrationDir('second'));
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Directory "xxx" doesn\'t exist. Use: first, second');
        $config->getMigrationDir('xxx');
    }

    public function testEmptyEnvironments()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Empty environments');
        new Config([
            'migration_dirs' => [
                'first_dir'
            ],
        ]);
    }
}
