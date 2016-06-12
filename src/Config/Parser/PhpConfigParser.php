<?php

namespace Phoenix\Config\Parser;

class PhpConfigParser implements ConfigParserInterface
{
    public function parse($filename)
    {
        return require $filename;
    }
}
