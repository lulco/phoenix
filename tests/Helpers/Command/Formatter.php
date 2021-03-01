<?php

namespace Phoenix\Tests\Helpers\Command;

use Symfony\Component\Console\Formatter\NullOutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class Formatter implements OutputFormatterInterface
{
    public function format(?string $message)
    {
    }

    public function getStyle(string $name)
    {
        return new NullOutputFormatterStyle();
    }

    public function hasStyle(string $name)
    {
        return false;
    }

    public function isDecorated()
    {
        return false;
    }

    public function setDecorated(bool $decorated)
    {
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style)
    {
    }
}
