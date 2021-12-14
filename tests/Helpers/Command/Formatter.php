<?php

namespace Phoenix\Tests\Helpers\Command;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class Formatter implements OutputFormatterInterface
{
    private bool $isDecorated = false;

    public function format(?string $message): ?string
    {
        return $message;
    }

    public function getStyle(string $name)
    {
    }

    public function hasStyle(string $name)
    {
    }

    public function isDecorated(): bool
    {
        return $this->isDecorated;
    }

    public function setDecorated(bool $decorated): void
    {
        $this->isDecorated = $decorated;
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style)
    {
    }
}
