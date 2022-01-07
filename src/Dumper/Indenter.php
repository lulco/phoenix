<?php

declare(strict_types=1);

namespace Phoenix\Dumper;

final class Indenter
{
    public function indent(string $identifier = '4spaces'): string
    {
        $indent = strtolower(str_replace([' ', '-', '_'], '', $identifier));
        if ($indent === '2spaces') {
            return '  ';
        }
        if ($indent === '3spaces') {
            return '   ';
        }
        if ($indent === '5spaces') {
            return '     ';
        }
        if ($indent === 'tab') {
            return "\t";
        }
        return '    ';
    }
}
