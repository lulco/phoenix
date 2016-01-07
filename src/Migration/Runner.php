<?php

namespace Phoenix\Migration;

class Runner
{
    private $migrations = [];
    
    private $migrationsExecuted = 0;
    
    /**
     * @param AbstractMigration $migration
     */
    public function addMigration(AbstractMigration $migration)
    {
        $this->migrations[] = $migration;
    }
    
    public function up()
    {
        foreach ($this->migrations as $migration) {
            $migration->migrate();
            $this->migrationsExecuted++;
        }
        return $this->migrationsExecuted;
    }
    
    public function down()
    {
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->rollback();
            $this->migrationsExecuted++;
        }
        return $this->migrationsExecuted;
    }
}
