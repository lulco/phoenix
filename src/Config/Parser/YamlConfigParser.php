<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

use Phoenix\Exception\ConfigException;
use Symfony\Component\Yaml\Yaml;

final class YamlConfigParser implements ConfigParserInterface
{
    public function parse(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new ConfigException('File "' . $filename . '" not found');
        }
        if (!class_exists('Symfony\Component\Yaml\Yaml')) {
            throw new ConfigException('Class Symfony\Component\Yaml\Yaml doesn\'t exist. Run composer require symfony/yaml');
        }
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), (string)file_get_contents($filename));
        return Yaml::parse($configString);
    }
}
