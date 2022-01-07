<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TestCommand extends AbstractCommand
{
    /** @var array<int, array{classname: string, type: string, execution_time: float}> */
    private array $executedMigrations = [];

    public function __construct(string $name = 'test')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('cleanup', null, InputOption::VALUE_NONE, 'Cleanup after test (rollback migration at the end)')
            ->setDescription('Test next migration');
    }

    protected function runCommand(): void
    {
        $this->writeln(['', '<comment>Test started...</comment>']);

        if ($this->migrate() === 0) {
            $this->writeln(['', '<comment>Nothing to test</comment>']);
            $this->outputData['executed_migrations'] = $this->executedMigrations;
            return;
        }

        $this->rollback();
        $this->migrate();
        if ($this->input->getOption('cleanup')) {
            $this->rollback();
        }

        $this->writeln(['', '<comment>Test finished successfully</comment>']);
        $this->outputData['executed_migrations'] = $this->executedMigrations;
    }

    private function migrate(): int
    {
        $upMigrations = $this->manager->findMigrationsToExecute(Manager::TYPE_UP, Manager::TARGET_FIRST);
        foreach ($upMigrations as $upMigration) {
            $this->writeln(['', '<info>Migration ' . $upMigration->getClassName() . ' executing...</info>']);
            $start = microtime(true);
            $upMigration->migrate();
            $executionTime = microtime(true) - $start;
            $this->manager->logExecution($upMigration);
            $this->writeln(['<info>Migration ' . $upMigration->getClassName() . ' executed.</info> <comment>Took ' . sprintf('%.4fs', $executionTime) . '</comment>']);
            $this->logMigration($upMigration, 'migrate', $executionTime);
        }
        return count($upMigrations);
    }

    private function rollback(): void
    {
        $downMigrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN, Manager::TARGET_FIRST);
        foreach ($downMigrations as $downMigration) {
            $this->writeln(['', '<info>Rollback for migration ' . $downMigration->getClassName() . ' executing...</info>']);
            $start = microtime(true);
            $downMigration->rollback();
            $executionTime = microtime(true) - $start;
            $this->manager->removeExecution($downMigration);
            $this->writeln(['<info>Rollback for migration ' . $downMigration->getClassName() . ' executed.</info> <comment>Took ' . sprintf('%.4fs', $executionTime) . '</comment>']);
            $this->logMigration($downMigration, 'rollback', $executionTime);
        }
    }

    private function logMigration(AbstractMigration $migration, string $type, float $executionTime): void
    {
        $executedQueries = $migration->getExecutedQueries();
        $this->writeln(['Executed queries:'], OutputInterface::VERBOSITY_DEBUG);
        $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

        $executedMigration = [
            'classname' => $migration->getClassName(),
            'type' => $type,
            'execution_time' => $executionTime,
        ];
        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $executedMigration['executed_queries'] = $executedQueries;
        }
        $this->executedMigrations[] = $executedMigration;
    }
}
