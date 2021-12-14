<?php

namespace Phoenix\Tests\Mock\Command;

use Phoenix\Tests\Helpers\Command\Formatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Output implements OutputInterface
{
    private array $messages = [];

    private OutputFormatterInterface $formatter;

    private int $verbosity = self::VERBOSITY_NORMAL;

    public function __construct(?OutputFormatterInterface $formatter = null)
    {
        $this->formatter = $formatter ?: new Formatter();
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->formatter;
    }

    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function isDecorated(): bool
    {
        return $this->formatter->isDecorated();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->formatter->setDecorated($decorated);
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function setVerbosity(int $level): void
    {
        $this->verbosity = $level;
    }

    public function write($messages, bool $newline = false, int $options = 0): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            $this->messages[$options][] = $message . ($newline ? "\n" : '');
        }
    }

    public function writeln($messages, int $options = 0): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            $this->messages[$options][] = $message . "\n";
        }
    }

    public function getMessages(int $verbosity = null): array
    {
        if ($verbosity === null) {
            return $this->messages;
        }

        return $this->messages[$verbosity] ?? [];
    }

    public function isDebug(): bool
    {
        return self::VERBOSITY_DEBUG <= $this->verbosity;
    }

    public function isQuiet(): bool
    {
        return self::VERBOSITY_QUIET === $this->verbosity;
    }

    public function isVerbose(): bool
    {
        return self::VERBOSITY_VERBOSE <= $this->verbosity;
    }

    public function isVeryVerbose(): bool
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->verbosity;
    }
}
