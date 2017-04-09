<?php

namespace Phoenix\Tests\Mock\Command;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Output implements OutputInterface
{
    private $messages = [];

    /** @var OutputFormatterInterface */
    private $formatter;

    private $verbosity = self::VERBOSITY_NORMAL;

    public function getFormatter()
    {
        return $this->formatter;
    }

    public function getVerbosity()
    {
        return $this->verbosity;
    }

    public function isDecorated()
    {
        return $this->formatter->isDecorated();
    }

    public function setDecorated($decorated)
    {
        $this->formatter->setDecorated($decorated);
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function setVerbosity($level)
    {
        $this->verbosity = $level;
    }

    public function write($messages, $newline = false, $options = 0)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            $this->messages[$options][] = $message . ($newline ? "\n" : '');
        }
    }

    public function writeln($messages, $options = 0)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            $this->messages[$options][] = $message . "\n";
        }
    }

    public function getMessages($verbosity = null)
    {
        if ($verbosity === null) {
            return $this->messages;
        }

        return isset($this->messages[$verbosity]) ? $this->messages[$verbosity] : [];
    }

    public function isDebug()
    {
        return self::VERBOSITY_DEBUG <= $this->verbosity;
    }

    public function isQuiet()
    {
        return self::VERBOSITY_QUIET === $this->verbosity;
    }

    public function isVerbose()
    {
        return self::VERBOSITY_VERBOSE <= $this->verbosity;
    }

    public function isVeryVerbose()
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->verbosity;
    }
}
