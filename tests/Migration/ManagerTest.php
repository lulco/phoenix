<?php

namespace Phoenix\Tests\Migration;

use PDO;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;
use Phoenix\Tests\Mock\Migration\FakeMigration;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    private $manager;

    private $adapter;

    private $initMigration;

    protected function setUp()
    {
        $config = new Config([
            'migration_dirs' => [
                __DIR__ . '/../fake/structure/migration_directory_1/',
            ],
            'environments' => [
                'sqlite' => [
                    'adapter' => 'sqlite',
                    'dsn' => 'sqlite::memory:'
                ],
            ]
        ]);
        $environmentConfig = $config->getEnvironmentConfig('sqlite');
        $pdo = new PDO($environmentConfig->getDsn());
        $this->adapter = new SqliteAdapter($pdo);
        $this->manager = new Manager($config, $this->adapter);

        $this->initMigration = new Init($this->adapter, $config->getLogTableName());
        $this->initMigration->migrate();
    }

    public function testMigrations()
    {
        $executedMigrations = $this->manager->executedMigrations();
        $this->assertTrue(is_array($executedMigrations));
        $this->assertCount(0, $executedMigrations);

        $migrations = $this->manager->findMigrationsToExecute();
        $this->checkMigrations($migrations, 2, [0 => '20150428140909', 1 => '20150518091732']);
        $this->assertTrue(is_array($migrations));

        $firstUpMigration = $this->manager->findMigrationsToExecute('up', 'first');
        $this->checkMigrations($firstUpMigration, 1, [0 => '20150428140909']);

        $downMigrations = $this->manager->findMigrationsToExecute('down');
        $this->checkMigrations($downMigrations, 0, []);

        $count = 0;
        foreach ($migrations as $migration) {
            $migration->migrate();
            $this->manager->logExecution($migration);
            $count++;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());

            $migration->rollback();
            $this->manager->removeExecution($migration);
            $count--;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());

            $migration->migrate();
            $this->manager->logExecution($migration);
            $count++;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());
        }

        $this->assertEquals(2, $count);
        $this->assertCount($count, $migrations);

        $firstDownMigration = $this->manager->findMigrationsToExecute('down', 'first');
        $this->checkMigrations($firstDownMigration, 1, [0 => '20150518091732']);

        $downMigrations = $this->manager->findMigrationsToExecute('down');
        $this->checkMigrations($downMigrations, 2, [0 => '20150518091732', 1 => '20150428140909']);

        $this->initMigration->rollback();

        $this->expectException(DatabaseQueryExecuteException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: no such table: phoenix_log.');
        $this->manager->executedMigrations();
    }

    public function testWrongType()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Type "type" is not allowed.');
        $this->manager->findMigrationsToExecute('type');
    }

    public function testWrongTarget()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Target "target" is not allowed.');
        $this->manager->findMigrationsToExecute('up', 'target');
    }

    public function testSkippingNonExistingMigration()
    {
        $executedMigrations = $this->manager->executedMigrations();
        $this->assertTrue(is_array($executedMigrations));
        $this->assertCount(0, $executedMigrations);

        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN);
        $this->assertTrue(is_array($migrations));
        $this->assertEmpty($migrations);

        $this->manager->logExecution(new FakeMigration($this->adapter));

        $executedMigrations = $this->manager->executedMigrations();
        $this->assertTrue(is_array($executedMigrations));
        $this->assertCount(1, $executedMigrations);

        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN);
        $this->assertTrue(is_array($migrations));
        $this->assertEmpty($migrations);
    }

    public function testExecuteLatestMigrationFirst()
    {
        $oldName = __DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php';
        $newName = __DIR__ . '/../fake/structure/migration_directory_2/20150428140909_first_migration.php';
        rename($oldName, $newName);

        $migrations = $this->manager->findMigrationsToExecute();
        $this->checkMigrations($migrations, 1, [0 => '20150518091732']);
        foreach ($migrations as $migration) {
            $migration->migrate();
            $this->manager->logExecution($migration);
        }

        sleep(2);
        rename($newName, $oldName);

        $migrations = $this->manager->findMigrationsToExecute();
        $this->checkMigrations($migrations, 1, [0 => '20150428140909']);
        foreach ($migrations as $migration) {
            $migration->migrate();
            $this->manager->logExecution($migration);
        }

        $downMigrations = $this->manager->findMigrationsToExecute('down');
        $this->checkMigrations($downMigrations, 2, [0 => '20150428140909', 1 => '20150518091732']);
        $this->initMigration->rollback();
    }

    private function checkMigrations($migrations, $count, array $migrationDatetimes = [])
    {
        $this->assertTrue(is_array($migrations));
        $this->assertCount($count, $migrations);
        $numberOfMigrations = count($migrations);
        for ($i = 0; $i < $numberOfMigrations; ++$i) {
            $this->assertInstanceOf(AbstractMigration::class, $migrations[$i]);
            $this->assertEquals($migrationDatetimes[$i], $migrations[$i]->getDatetime());
        }
    }
}
