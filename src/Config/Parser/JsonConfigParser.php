<?php

namespace Phoenix\Config\Parser;

class JsonConfigParser implements ConfigParserInterface
{
    public function parse($filename)
    {
        $configString = str_replace('%%ACTUAL_DIR%%', pathinfo($filename, PATHINFO_DIRNAME), file_get_contents($filename));
        return json_decode($configString, true);
    }
}
