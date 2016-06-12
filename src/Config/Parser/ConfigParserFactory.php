<?php

namespace Phoenix\Config\Parser;

use Phoenix\Exception\ConfigException;

class ConfigParserFactory
{
    public static function instance($type)
    {
        $type = strtolower($type);
        switch ($type) {
            case 'php':
                return new PhpConfigParser();
            case 'yml':
            case 'yaml':
                return new YamlConfigParser();
            case 'neon':
                return new NeonConfigParser();
            default:
                throw new ConfigException('Unknown config type "' . $type . '"');
        }
    }
}
