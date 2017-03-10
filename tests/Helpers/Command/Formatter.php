<?php

namespace Phoenix\Tests\Helpers\Command;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class Formatter implements OutputFormatterInterface
{
    public function format($message)
    {
    }

    public function getStyle($name)
    {
    }

    public function hasStyle($name)
    {
    }

    public function isDecorated()
    {
    }

    public function setDecorated($decorated)
    {
    }

    public function setStyle($name, OutputFormatterStyleInterface $style)
    {
    }
}
