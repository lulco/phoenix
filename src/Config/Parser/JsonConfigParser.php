<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

use Phoenix\Exception\ConfigException;

final class JsonConfigParser implements ConfigParserInterface
{
    public function parse(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new ConfigException('File "' . $filename . '" not found');
        }
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), (string)file_get_contents($filename));
        return json_decode($configString, true);
    }
}
