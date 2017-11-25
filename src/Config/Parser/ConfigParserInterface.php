<?php

namespace Phoenix\Config\Parser;

interface ConfigParserInterface
{
    public function parse(string $filename): array;
}
