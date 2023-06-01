<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

use Phoenix\Behavior\ParamsCheckerBehavior;

final class UniqueConstraint
{
    use ParamsCheckerBehavior;

    /** @var string[] */
    private array $columns;

    private string $name;

    /**
     * @param string[] $columns
     * @param string $name
     */
    public function __construct(array $columns, string $name)
    {
        $this->columns = $columns;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
