<?php

namespace Phoenix\Config\Parser;

use Nette\Neon\Neon;

class NeonConfigParser implements ConfigParserInterface
{
    public function parse($filename)
    {
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), file_get_contents($filename));
        return Neon::decode($configString);
    }
}
