<?php

declare(strict_types=1);

namespace Phoenix\Migration\Init;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class Init extends AbstractMigration
{
    private string $logTableName;

    public function __construct(AdapterInterface $adapter, string $logTableName)
    {
        parent::__construct($adapter);
        $this->logTableName = $logTableName;
    }

    /**
     * @throws InvalidArgumentValueException
     */
    protected function up(): void
    {
        $this->table($this->logTableName)
            ->addColumn('migration_datetime', Column::TYPE_STRING)
            ->addColumn('classname', Column::TYPE_STRING)
            ->addColumn('executed_at', Column::TYPE_DATETIME)
            ->create();
    }

    protected function down(): void
    {
        $this->table($this->logTableName)
            ->drop();
    }
}
