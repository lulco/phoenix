<?php

namespace Phoenix\Config\Parser;

interface ConfigParserInterface
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $filename): array;
}
