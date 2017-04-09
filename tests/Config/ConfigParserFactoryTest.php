<?php

namespace Phoenix\Tests\Config;

use Phoenix\Config\Parser\ConfigParserFactory;
use Phoenix\Config\Parser\JsonConfigParser;
use Phoenix\Config\Parser\NeonConfigParser;
use Phoenix\Config\Parser\PhpConfigParser;
use Phoenix\Config\Parser\YamlConfigParser;
use Phoenix\Exception\ConfigException;
use PHPUnit_Framework_TestCase;

class ConfigParserFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(PhpConfigParser::class, ConfigParserFactory::instance('php'));
        $this->assertInstanceOf(PhpConfigParser::class, ConfigParserFactory::instance('PHP'));
        $this->assertInstanceOf(PhpConfigParser::class, ConfigParserFactory::instance('Php'));

        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('yml'));
        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('yaml'));
        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('YML'));
        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('YAML'));
        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('Yml'));
        $this->assertInstanceOf(YamlConfigParser::class, ConfigParserFactory::instance('Yaml'));

        $this->assertInstanceOf(NeonConfigParser::class, ConfigParserFactory::instance('neon'));
        $this->assertInstanceOf(NeonConfigParser::class, ConfigParserFactory::instance('NEON'));
        $this->assertInstanceOf(NeonConfigParser::class, ConfigParserFactory::instance('Neon'));

        $this->assertInstanceOf(JsonConfigParser::class, ConfigParserFactory::instance('json'));
        $this->assertInstanceOf(JsonConfigParser::class, ConfigParserFactory::instance('JSON'));
        $this->assertInstanceOf(JsonConfigParser::class, ConfigParserFactory::instance('Json'));
    }

    public function testUnknownType()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unknown config type "asdf"');
        ConfigParserFactory::instance('Asdf');
    }
}
