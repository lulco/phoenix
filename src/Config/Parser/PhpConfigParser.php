<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

use Phoenix\Exception\ConfigException;

final class PhpConfigParser implements ConfigParserInterface
{
    public function parse(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new ConfigException('File "' . $filename . '" not found');
        }
        return require $filename;
    }
}
