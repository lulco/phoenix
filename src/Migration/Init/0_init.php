<?php

namespace Phoenix\Migration\Init;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    private $logTableName;
    
    public function __construct(AdapterInterface $adapter, $logTableName)
    {
        parent::__construct($adapter);
        $this->logTableName = $logTableName;
    }
    
    protected function up()
    {
        $this->table($this->logTableName)
            ->addColumn('migration_datetime', 'string')
            ->addColumn('classname', 'string')
            ->addColumn('executed_at', 'datetime')
            ->create();
    }

    protected function down()
    {
        $this->table($this->logTableName)
            ->drop();
    }
}
