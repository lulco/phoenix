<?php

namespace Phoenix\Database\Element\Behavior;

use Phoenix\Database\Element\MigrationTable;

trait CharsetAndCollationBehavior
{
    private ?string $charset;

    private ?string $collation;

    public function setCharset(?string $charset): MigrationTable
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCollation(?string $collation): MigrationTable
    {
        $this->collation = $collation;
        return $this;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }
}
