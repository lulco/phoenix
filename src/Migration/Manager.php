<?php

namespace Phoenix\Migration;

use DateTime;
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

    private $config;

    private $adapter;

    public function __construct(Config $config, AdapterInterface $adapter)
    {
        $this->config = $config;
        $this->adapter = $adapter;
    }

    /**
     * @return AbstractMigration[]
     * @throws InvalidArgumentValueException
     */
    public function findMigrationsToExecute(string $type = self::TYPE_UP, string $target = self::TARGET_ALL, array $dirs = [], array $classes = []): array
    {
        $this->inArray($type, [self::TYPE_UP, self::TYPE_DOWN], 'Type "' . $type . '" is not allowed.');
        $this->inArray($target, [self::TARGET_ALL, self::TARGET_FIRST], 'Target "' . $target . '" is not allowed.');

        $migrations = $this->findMigrations($type, $dirs, $classes);
        if (empty($migrations)) {
            return [];
        }
        if ($type === self::TYPE_DOWN) {
            $migrations = array_reverse($migrations);
        }
        return $target === self::TARGET_ALL ? $migrations : [current($migrations)];
    }

    private function findMigrations(string $type, array $dirs, array $classes): array
    {
        $migrations = $this->findMigrationClasses($dirs, $classes);
        $executedMigrations = $this->executedMigrations();
        if ($type === self::TYPE_UP) {
            foreach (array_keys($executedMigrations) as $migrationIdentifier) {
                unset($migrations[$migrationIdentifier]);
            }
            ksort($migrations);
            return array_values($migrations);
        }

        $migrationsToExecute = [];
        foreach (array_keys($executedMigrations) as $migrationIdentifier) {
            if (!isset($migrations[$migrationIdentifier])) {
                continue;
            }
            $migrationsToExecute[] = $migrations[$migrationIdentifier];
        }
        return $migrationsToExecute;
    }

    private function findMigrationClasses(array $dirs = [], array $classes = []): array
    {
        $filesFinder = new FilesFinder();
        foreach ($this->config->getMigrationDirs() as $identifier => $directory) {
            if (empty($dirs) || (!empty($dirs) && in_array($identifier, $dirs))) {
                $filesFinder->addDirectory($directory);
            }
        }

        $migrations = [];
        foreach ($filesFinder->getFiles() as $file) {
            require_once $file;
            $classNameCreator = new ClassNameCreator($file);
            $className = $classNameCreator->getClassName();
            if (empty($classes) || (!empty($classes) && in_array($className, $classes))) {
                $migrationIdentifier = $classNameCreator->getDatetime() . '|' . $className;
                $migrations[$migrationIdentifier] = new $className($this->adapter);
            }
        }
        return $migrations;
    }

    public function executedMigrations(): array
    {
        $migrations = $this->adapter->fetchAll($this->config->getLogTableName(), ['*'], [], null, ['executed_at', 'migration_datetime']);
        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $executedMigrations[$migration['migration_datetime'] . '|' . $migration['classname']] = $migration;
        }
        return $executedMigrations;
    }

    public function logExecution(AbstractMigration $migration): void
    {
        $data = $this->createData($migration);
        $data['executed_at'] = new DateTime();
        $this->adapter->insert($this->config->getLogTableName(), $data);
    }

    public function removeExecution(AbstractMigration $migration): void
    {
        $this->adapter->delete($this->config->getLogTableName(), $this->createData($migration));
    }

    private function createData(AbstractMigration $migration): array
    {
        return [
            'classname' => $migration->getFullClassName(),
            'migration_datetime' => $migration->getDatetime(),
        ];
    }
}
