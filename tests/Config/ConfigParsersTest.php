<?php

namespace Phoenix\Tests\Config;

use Exception;
use Phoenix\Config\Parser\ConfigParserFactory;
use Phoenix\Exception\ConfigException;
use PHPUnit_Framework_TestCase;

class ConfigParsersTest extends PHPUnit_Framework_TestCase
{
    public function testParser()
    {
        $configs = [
            'php' => [
                'file' => 'phoenix.php',
            ],
            'json' => [
                'file' => 'phoenix.json',
            ],
            'yml' => [
                'file' => 'phoenix.yml',
                'class' => 'Symfony\Component\Yaml\Yaml',
            ],
            'neon' => [
                'file' => 'phoenix.neon',
                'class' => 'Nette\Neon\Neon',
            ],
        ];

        foreach ($configs as $type => $configuration) {
            $configParser = ConfigParserFactory::instance($type);
            $filename = __DIR__ . '/../../example/' . $configuration['file'];
            $classname = isset($configuration['class']) ? $configuration['class'] : null;
            if ($classname && class_exists($classname)) {
                $config = $configParser->parse($filename);
            } else {
                try {
                    $config = $configParser->parse($filename);
                } catch (Exception $e) {
                    $this->assertInstanceOf(ConfigException::class, $e);
                    $this->assertContains($classname, $e->getMessage());
                }
            }
            $this->assertArrayHasKey('migration_dirs', $config);
            $this->assertArrayHasKey('phoenix', $config['migration_dirs']);
            $this->assertArrayHasKey('environments', $config);
            $this->assertArrayHasKey('mysql', $config['environments']);
            $this->assertArrayHasKey('sqlite', $config['environments']);
        }
    }
}
