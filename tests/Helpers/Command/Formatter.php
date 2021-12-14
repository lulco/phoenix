<?php

namespace Phoenix\Tests\Helpers\Command;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class Formatter implements OutputFormatterInterface
{
    public function format(?string $message)
    {
    }

    public function getStyle(string $name)
    {
    }

    public function hasStyle(string $name)
    {
    }

    public function isDecorated()
    {
    }

    public function setDecorated(bool $decorated)
    {
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style)
    {
    }
}
