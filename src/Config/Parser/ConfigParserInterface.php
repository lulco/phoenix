<?php

declare(strict_types=1);

namespace Phoenix\Config\Parser;

interface ConfigParserInterface
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $filename): array;
}
