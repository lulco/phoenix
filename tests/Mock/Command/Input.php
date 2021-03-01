<?php

namespace Phoenix\Tests\Mock\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class Input implements InputInterface
{
    private $arguments = [];
    
    private $options = [];
    
    public function bind(InputDefinition $definition)
    {
        
    }

    public function getArgument(string $name)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : null;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getFirstArgument()
    {
        return null;
    }

    public function getOption(string $name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getParameterOption($values, $default = false, bool $onlyParams = false)
    {
        return null;
    }

    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    public function hasOption(string $name)
    {
        return isset($this->options[$name]);
    }

    public function hasParameterOption($values, bool $onlyParams = false)
    {
        return false;
    }

    public function isInteractive()
    {
        return false;
    }

    public function setArgument(string $name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    public function setInteractive(bool $interactive)
    {
        
    }

    public function setOption(string $name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function validate()
    {
        return true;
    }
}
