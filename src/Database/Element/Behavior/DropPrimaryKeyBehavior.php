<?php

declare(strict_types=1);

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait DropPrimaryKeyBehavior
{
    private bool $dropPrimaryKey = false;

    public function dropPrimaryKey(): MigrationTable
    {
        $this->dropPrimaryKey = true;
        return $this;
    }

    public function hasPrimaryKeyToDrop(): bool
    {
        return $this->dropPrimaryKey;
    }
}
