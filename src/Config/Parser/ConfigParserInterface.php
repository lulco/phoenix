<?php

namespace Phoenix\Config\Parser;

interface ConfigParserInterface
{
    /**
     * @param string $filename
     */
    public function parse($filename);
}
