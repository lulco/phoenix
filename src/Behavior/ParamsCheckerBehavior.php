<?php

declare(strict_types=1);

namespace Phoenix\Behavior;

use Phoenix\Exception\InvalidArgumentValueException;

trait ParamsCheckerBehavior
{
    /**
     * @param string $valueToCheck
     * @param string[] $availableValues
     * @param string $message
     * @throws InvalidArgumentValueException
     */
    protected function inArray(string $valueToCheck, array $availableValues, string $message): void
    {
        if (!in_array($valueToCheck, $availableValues, true)) {
            throw new InvalidArgumentValueException($message);
        }
    }
}
