<?php

namespace Phoenix\Tests\Mock\Command;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Output implements OutputInterface
{
    private $messages = [];
    
    public function getFormatter()
    {
        
    }

    public function getVerbosity()
    {
        
    }

    public function isDecorated()
    {
        
    }

    public function setDecorated($decorated)
    {
        
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        
    }

    public function setVerbosity($level)
    {
        
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
}
