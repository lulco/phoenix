<?php

namespace Phoenix\Database\Element\Behavior;

trait AutoIncrementBehavior
{
    /** @var int|null */
    private $autoIncrement;

    public function setAutoIncrement(?int $autoIncrement): self
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function getAutoIncrement(): ?int
    {
        return $this->autoIncrement;
    }
}
