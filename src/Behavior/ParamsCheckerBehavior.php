<?php

namespace Phoenix\Behavior;

use Phoenix\Exception\InvalidArgumentValueException;

trait ParamsCheckerBehavior
{
    protected function inArray($valueToCheck, array $availableValues, $message = null)
    {
        if (!in_array($valueToCheck, $availableValues)) {
            throw new InvalidArgumentValueException($message);
        }
    }
}
