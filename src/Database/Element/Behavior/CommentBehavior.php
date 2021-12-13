<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait CommentBehavior
{
    private ?string $comment;

    public function setComment(?string $comment): MigrationTable
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function unsetComment(): MigrationTable
    {
        return $this->setComment('');
    }
}
