<?php

declare(strict_types=1);

namespace Phoenix\Tests\Config;

use Exception;
use Phoenix\Config\Parser\ConfigParserFactory;
use Phoenix\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

final class ConfigParsersTest extends TestCase
{
    public function testParser(): void
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
            $filename = __DIR__ . '/../../config_example/' . $configuration['file'];
            $classname = $configuration['class'] ?? null;
            $config = [];
            if ($classname && class_exists($classname)) {
                $config = $configParser->parse($filename);
            } else {
                try {
                    $config = $configParser->parse($filename);
                } catch (Exception $e) {
                    $this->assertInstanceOf(ConfigException::class, $e);
                    $this->assertStringContainsString($classname, $e->getMessage());
                }
            }
            $this->assertArrayHasKey('migration_dirs', $config);
            $this->assertArrayHasKey('phoenix', $config['migration_dirs']);
            $this->assertArrayHasKey('environments', $config);
            $this->assertArrayHasKey('mysql', $config['environments']);
        }
    }

    public function testConfigFileNotFound(): void
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
            ],
            'neon' => [
                'file' => 'phoenix.neon',
            ],
        ];

        $counter = 0;
        foreach ($configs as $type => $configuration) {
            $configParser = ConfigParserFactory::instance($type);
            $filename = __DIR__ . '/../../config_example/not_found_' . $configuration['file'];
            try {
                $configParser->parse($filename);
            } catch (Exception $e) {
                $counter++;
                $this->assertInstanceOf(ConfigException::class, $e);
                $this->assertEquals('File "' . $filename . '" not found', $e->getMessage());
            }
        }
        $this->assertEquals(4, $counter);
    }
}
