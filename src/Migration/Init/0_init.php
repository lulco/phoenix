<?php

namespace Phoenix\Migration\Init;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    /** @var string */
    private $logTableName;

    public function __construct(AdapterInterface $adapter, string $logTableName)
    {
        parent::__construct($adapter);
        $this->logTableName = $logTableName;
    }

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
