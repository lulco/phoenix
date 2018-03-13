<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Behavior\ParamsCheckerBehavior;

trait CopyTableBehavior
{
    use ParamsCheckerBehavior;
    
    private $action;

    private $newName;

    private $copyType;

    public function getCopyType(): string
    {
        return $this->copyType;
    }

    public function copy(string $newName, string $copyType = self::COPY_ONLY_STRUCTURE): void
    {
        $this->inArray($copyType, [self::COPY_ONLY_STRUCTURE, self::COPY_ONLY_DATA, self::COPY_STRUCTURE_AND_DATA], 'Copy type "' . $copyType . '" is not allowed');

        $this->action = self::ACTION_COPY;
        $this->newName = $newName;
        $this->copyType = $copyType;
    }
}
