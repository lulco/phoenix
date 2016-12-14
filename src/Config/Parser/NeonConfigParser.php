<?php

namespace Phoenix\Config\Parser;

use Nette\Neon\Neon;
use Phoenix\Exception\ConfigException;

class NeonConfigParser implements ConfigParserInterface
{
    public function parse($filename)
    {
        if (!class_exists('Nette\Neon\Neon')) {
            throw new ConfigException('Class Nette\Neon\Neon doesn\'t exist. Run composer require nette/neon');
        }
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), file_get_contents($filename));
        return Neon::decode($configString);
    }
}
