<?php

namespace Phoenix\Tests\Config;

use Phoenix\Config\Parser\ConfigParserFactory;
use PHPUnit_Framework_TestCase;

class ConfigParsersTest extends PHPUnit_Framework_TestCase
{
    public function testParser()
    {
        $configs = [
            'php' => 'phoenix.php',
            'yml' => 'phoenix.yml',
            'neon' => 'phoenix.neon',
            'json' => 'phoenix.json',
        ];

        foreach ($configs as $type => $file) {
            $configParser = ConfigParserFactory::instance($type);
            $filename = __DIR__ . '/../../example/' . $file;
            $config = $configParser->parse($filename);
            $this->assertArrayHasKey('migration_dirs', $config);
            $this->assertArrayHasKey('phoenix', $config['migration_dirs']);
            $this->assertArrayHasKey('environments', $config);
            $this->assertArrayHasKey('mysql', $config['environments']);
            $this->assertArrayHasKey('sqlite', $config['environments']);
        }
    }
}
