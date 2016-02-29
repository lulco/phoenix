<?php

namespace Phoenix\Migration;

use Nette\Utils\DateTime;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\InvalidArgumentValueException;

class Manager
{
    const TYPE_UP = 'up';
    
    const TYPE_DOWN = 'down';
    
    const TARGET_FIRST = 'first';
    
    const TARGET_ALL = 'all';
    
    /** @var Config */
    private $config;
    
    /** @var AdapterInterface */
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
        if (!in_array($type, [self::TYPE_UP, self::TYPE_DOWN])) {
            throw new InvalidArgumentValueException('Type "' . $type . '" is not allowed.');
        }
        
        if (!in_array($target, [self::TARGET_ALL, self::TARGET_FIRST])) {
            throw new InvalidArgumentValueException('Target "' . $target . '" is not allowed.');
        }
        
        $migrations = $this->findMigrations($type);
        if (empty($migrations)) {
            return $migrations;
        }
        if ($type == self::TYPE_DOWN) {
            $migrations = array_reverse($migrations);
        }
        return $target == self::TARGET_ALL ? $migrations : [current($migrations)];
    }
    
    private function findMigrations($type)
    {
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
        ksort($migrations);
        return $migrations;
    }
    
    /**
     * returs executed migrations
     * @return array
     */
    public function executedMigrations()
    {
        $migrations = $this->adapter->fetchAll($this->config->getLogTableName(), '*');
        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $executedMigrations[$migration['migration_datetime'] . '|' . $migration['classname']] = $migration;
        }
        return $executedMigrations;
    }

    /**
     * adds migration to log table
     * @param AbstractMigration $migration
     */
    public function logExecution(AbstractMigration $migration)
    {
        $data = [
            'migration_datetime' => $migration->getDatetime(),
            'classname' => $migration->getFullClassName(),
            'executed_at' => new DateTime(),
        ];
        $this->adapter->insert($this->config->getLogTableName(), $data);
    }
    
    /**
     * removes migration from log table
     * @param AbstractMigration $migration
     */
    public function removeExecution(AbstractMigration $migration)
    {
        $this->adapter->delete($this->config->getLogTableName(), [
            'classname' => $migration->getFullClassName(),
            'migration_datetime' => $migration->getDatetime(),
        ]);
    }
}
