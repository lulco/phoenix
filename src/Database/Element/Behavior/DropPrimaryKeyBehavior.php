<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait DropPrimaryKeyBehavior
{
    /** @var bool */
    private $dropPrimaryKey = false;

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
