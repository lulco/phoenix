<?php

namespace Phoenix\Config\Parser;

interface ConfigParserInterface
{
    /**
     * @param string $filename
     * @return array<string, mixed>
     */
    public function parse(string $filename): array;
}
