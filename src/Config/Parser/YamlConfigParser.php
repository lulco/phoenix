<?php

namespace Phoenix\Config\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlConfigParser implements ConfigParserInterface
{
    public function parse($filename)
    {
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), file_get_contents($filename));
        return Yaml::parse($configString);
    }
}
