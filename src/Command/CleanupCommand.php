<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Output\OutputInterface;

final class CleanupCommand extends AbstractCommand
{
    public function __construct(string $name = 'cleanup')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Rollback all migrations and delete log table');
        parent::configure();
    }

    protected function runCommand(): void
    {
        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN);
        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $migration->rollback();
            $this->manager->removeExecution($migration);

            $this->writeln(['', '<info>Rollback for migration ' . $migration->getClassName() . ' executed</info>']);
            $executedMigrations[] = $this->addMigrationToList($migration);
        }

        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->getConfig()->getLogTableName());
        $migration->rollback();

        $this->writeln(['', '<info>Phoenix cleaned</info>']);
        $this->outputData['message'] = 'Phoenix cleaned';
        $executedMigrations[] = $this->addMigrationToList($migration);
        $this->writeln(['']);

        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $this->outputData['executed_migrations'] = $executedMigrations;
        }
    }

    /**
     * @param AbstractMigration $migration
     * @return array<string, mixed>
     */
    private function addMigrationToList(AbstractMigration $migration): array
    {
        $executedQueries = $migration->getExecutedQueries();
        $this->writeln(['Executed queries:'], OutputInterface::VERBOSITY_DEBUG);
        $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

        $executedMigration = [
            'classname' => $migration->getClassName(),
        ];
        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $executedMigration['executed_queries'] = $executedQueries;
        }
        return $executedMigration;
    }
}
