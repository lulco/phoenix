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

    public function getArgument($name)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : null;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getFirstArgument()
    {
        
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
        
    }

    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    public function hasParameterOption($values, $onlyParams = false)
    {
        
    }

    public function isInteractive()
    {

    }

    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    public function setInteractive($interactive)
    {
        
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function validate()
    {
        return true;
    }
}
