<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

use Nette\Neon\Neon;
use Phoenix\Exception\ConfigException;

final class NeonConfigParser implements ConfigParserInterface
{
    public function parse(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new ConfigException('File "' . $filename . '" not found');
        }
        if (!class_exists('Nette\Neon\Neon')) {
            throw new ConfigException('Class Nette\Neon\Neon doesn\'t exist. Run composer require nette/neon');
        }
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), (string)file_get_contents($filename));
        return Neon::decode($configString);
    }
}
