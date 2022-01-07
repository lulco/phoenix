<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Behavior\ParamsCheckerBehavior;
use Phoenix\Database\Element\MigrationTable;

trait CopyTableBehavior
{
    use ParamsCheckerBehavior;

    private ?string $newName = null;

    private string $copyType;

    public function getCopyType(): string
    {
        return $this->copyType;
    }

    public function copy(string $newName, string $copyType = MigrationTable::COPY_ONLY_STRUCTURE): void
    {
        $this->inArray($copyType, [MigrationTable::COPY_ONLY_STRUCTURE, MigrationTable::COPY_ONLY_DATA, MigrationTable::COPY_STRUCTURE_AND_DATA], 'Copy type "' . $copyType . '" is not allowed');

        $this->action = MigrationTable::ACTION_COPY;
        $this->newName = $newName;
        $this->copyType = $copyType;
    }
}
