<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Symfony\Component\Console\Helper\Table;

final class StatusCommand extends AbstractCommand
{
    public function __construct(string $name = 'status')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('List of migrations already executed and list of migrations to execute');
        parent::configure();
    }

    protected function runCommand(): void
    {
        $executedMigrations = [];
        foreach ($this->manager->executedMigrations() as $migration) {
            $executedMigrations[] = [
                'migration_datetime' => $migration['migration_datetime'],
                'classname' => ltrim($migration['classname'], '\\'),
                'executed_at' => $migration['executed_at'],
            ];
        }
        $this->outputData['executed_migrations'] = $executedMigrations;

        $migrationsToExecute = [];
        foreach ($this->manager->findMigrationsToExecute() as $migration) {
            $migrationsToExecute[] = [
                'migration_datetime' => $migration->getDatetime(),
                'classname' => $migration->getClassName()
            ];
        }
        $this->outputData['migrations_to_execute'] = $migrationsToExecute;

        if ($this->isDefaultOutput()) {
            $this->printTable(['Migration datetime', 'Class name', 'Executed at'], $executedMigrations, 'Executed migrations', 'No executed migrations');
            $this->printTable(['Migration datetime', 'Class name'], $migrationsToExecute, 'Migrations to execute', 'No migrations to execute');
        }
    }

    /**
     * @param string[] $headers
     * @param array<int, array<string, string|mixed>> $rows
     * @param string $header
     * @param string $noItemsText
     */
    private function printTable(array $headers, array $rows, string $header, string $noItemsText): void
    {
        $this->writeln(['', "<comment>$header</comment>"]);
        if (empty($rows)) {
            $this->writeln(["<info>$noItemsText</info>"]);
            return;
        }
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
