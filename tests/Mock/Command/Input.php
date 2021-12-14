<?php

namespace Phoenix\Tests\Mock\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class Input implements InputInterface
{
    private array $arguments = [];

    private array $options = [];

    public function bind(InputDefinition $definition): void
    {
    }

    public function getArgument(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getFirstArgument()
    {
    }

    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function hasParameterOption($values, bool $onlyParams = false)
    {

    }

    public function isInteractive(): bool
    {
        return false;
    }

    public function setArgument(string $name, $value): void
    {
        $this->arguments[$name] = $value;
    }

    public function setInteractive(bool $interactive): void
    {
    }

    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    public function validate(): void
    {
    }
}
