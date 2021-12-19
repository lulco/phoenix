<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

use Phoenix\Exception\ConfigException;

final class ConfigParserFactory
{
    public static function instance(string $type): ConfigParserInterface
    {
        $type = strtolower($type);
        if ($type === 'php') {
            return new PhpConfigParser();
        }
        if (in_array($type, ['yml', 'yaml'], true)) {
            return new YamlConfigParser();
        }
        if ($type === 'neon') {
            return new NeonConfigParser();
        }
        if ($type === 'json') {
            return new JsonConfigParser();
        }
        throw new ConfigException('Unknown config type "' . $type . '"');
    }
}
