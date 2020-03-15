<?php

namespace Phoenix\Behavior;

use Phoenix\Exception\InvalidArgumentValueException;

trait ParamsCheckerBehavior
{
    protected function inArray(string $valueToCheck, array $availableValues, string $message): void
    {
        if (!in_array($valueToCheck, $availableValues, true)) {
            throw new InvalidArgumentValueException($message);
        }
    }
}
