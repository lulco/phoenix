<?php

namespace Phoenix\Migration;

use Nette\Utils\DateTime;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Behavior\ParamsCheckerBehavior;

class Manager
{
    use ParamsCheckerBehavior;

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
     * @param string $type up / down
     * @param string $target all / first
     * @return AbstractMigration[]
     * @throws InvalidArgumentValueException
     */
    public function findMigrationsToExecute($type = self::TYPE_UP, $target = self::TARGET_ALL)
    {
        $this->inArray($type, [self::TYPE_UP, self::TYPE_DOWN], 'Type "' . $type . '" is not allowed.');
        $this->inArray($target, [self::TARGET_ALL, self::TARGET_FIRST], 'Target "' . $target . '" is not allowed.');

        $migrations = $this->findMigrations($type);
        if (empty($migrations)) {
            return [];
        }
        if ($type == self::TYPE_DOWN) {
            $migrations = array_reverse($migrations);
        }
        return $target == self::TARGET_ALL ? $migrations : [current($migrations)];
    }

    private function findMigrations($type)
    {
        $migrations = $this->findMigrationClasses();
        $executedMigrations = $this->executedMigrations();
        if ($type == self::TYPE_UP) {
            foreach (array_keys($executedMigrations) as $migrationIdentifier) {
                unset($migrations[$migrationIdentifier]);
            }
            ksort($migrations);
            return array_values($migrations);
        }

        $migrationsToExecute = [];
        foreach (array_keys($executedMigrations) as $migrationIdentifier) {
            $migrationsToExecute[] = $migrations[$migrationIdentifier];
        }
        return $migrationsToExecute;
    }

    private function findMigrationClasses()
    {
        $filesFinder = new FilesFinder();
        foreach ($this->config->getMigrationDirs() as $directory) {
            $filesFinder->addDirectory($directory);
        }

        $migrations = [];
        foreach ($filesFinder->getFiles() as $file) {
            require_once $file;
            $classNameCreator = new ClassNameCreator($file);
            $className = $classNameCreator->getClassName();
            $migrationIdentifier = $classNameCreator->getDatetime() . '|' . $className;
            $migrations[$migrationIdentifier] = new $className($this->adapter);
        }
        return $migrations;
    }

    /**
     * returs executed migrations
     * @return array
     */
    public function executedMigrations()
    {
        $migrations = $this->adapter->fetchAll($this->config->getLogTableName(), '*', [], null, ['executed_at', 'migration_datetime']);
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
        $data = $this->createData($migration);
        $data['executed_at'] = new DateTime();
        $this->adapter->insert($this->config->getLogTableName(), $data);
    }

    /**
     * removes migration from log table
     * @param AbstractMigration $migration
     */
    public function removeExecution(AbstractMigration $migration)
    {
        $this->adapter->delete($this->config->getLogTableName(), $this->createData($migration));
    }

    private function createData(AbstractMigration $migration)
    {
        return [
            'classname' => $migration->getFullClassName(),
            'migration_datetime' => $migration->getDatetime(),
        ];
    }
}
