<?php

namespace Phoenix\Migration;

use Nette\Utils\DateTime;
use PDO;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\AdapterInterface;

class Manager
{
    const TYPE_UP = 'up';
    
    const TYPE_DOWN = 'down';
    
    const TARGET_FIRST = 'first';
    
    const TARGET_ALL = 'all';
    
    private $config;
    
    private $adapter;
    
    /**
     * @param Config $config
     * @param AdapterInterface $adapter
     */
    public function __construct(Config $config, AdapterInterface $adapter)
    {
        $this->config = $config;
        $this->adapter = $adapter;
    }
    
    /**
     * @return AbstractMigration[]
     */
    public function findMigrationsToExecute($type = self::TYPE_UP, $target = self::TARGET_ALL)
    {
        // TODO check input parameters
        
        $filesFinder = new FilesFinder();
        foreach ($this->config->getMigrationDirs() as $directory) {
            $filesFinder->addDirectory($directory);
        }

        $executedMigrations = $this->executedMigrations();
                
        $migrations = [];
        foreach ($filesFinder->getFiles() as $file) {
            require_once $file;
            $classNameCreator = new ClassNameCreator($file);
            $className = $classNameCreator->getClassName();
            $migrationIdentifier = $classNameCreator->getDatetime() . '|' . $className;
            if ($type == self::TYPE_UP && !isset($executedMigrations[$migrationIdentifier])) {
                $migrations[$migrationIdentifier] = new $className($this->adapter);
            } elseif ($type == self::TYPE_DOWN && isset($executedMigrations[$migrationIdentifier])) {
                $migrations[$migrationIdentifier] = new $className($this->adapter);
            }
        }

        if (empty($migrations)) {
            return $migrations;
        }
        
        ksort($migrations);
        if ($type == self::TYPE_DOWN) {
            $migrations = array_reverse($migrations);
        }
        return $target == self::TARGET_ALL ? $migrations : [current($migrations)];
    }
    
    public function executedMigrations()
    {
        $migrations = $this->adapter->execute('SELECT * FROM `' . $this->config->getLogTableName() . '`')->fetchAll(PDO::FETCH_ASSOC);
        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $executedMigrations[$migration['migration_datetime'] . '|' . $migration['classname']] = $migration;
        }
        return $executedMigrations;
    }

    public function logExecution(AbstractMigration $migration)
    {
        $data = [
            'migration_datetime' => $migration->getDatetime(),
            'classname' => $migration->getFullClassName(),
            'executed_at' => new DateTime(),
        ];
        
        // use new insert method and $data array instead of this query
        $this->adapter->execute('INSERT INTO `' . $this->config->getLogTableName() . '` (`migration_datetime`, `classname`, `executed_at`) VALUES ("' . $migration->getDatetime() . '", "' . addslashes($migration->getFullClassName()) . '", "' . (new DateTime()) . '")');
    }
    
    public function removeExecution(AbstractMigration $migration)
    {
        $this->adapter->execute('DELETE FROM `' . $this->config->getLogTableName() . '` WHERE `classname`="' . addslashes($migration->getFullClassName()) . '" AND `migration_datetime`="' . $migration->getDatetime() . '"');
    }
}
