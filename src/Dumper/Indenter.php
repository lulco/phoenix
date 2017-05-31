<?php

namespace Dumper;

class Indenter
{
    public function indent($identifier = '4spaces')
    {
        $indent = strtolower(str_replace([' ', '-', '_'], '', $identifier));
        if ($indent == '2spaces') {
            return '  ';
        }
        if ($indent == '3spaces') {
            return '   ';
        }
        if ($indent == '5spaces') {
            return '     ';
        }
        if ($indent == 'tab') {
            return "\t";
        }
        return '    ';
    }
}
