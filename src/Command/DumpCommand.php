<?php

namespace Phoenix\Command;

use Phoenix\Database\Element\Structure;
use Symfony\Component\Console\Input\InputOption;

class DumpCommand extends AbstractDumpCommand
{
    protected function configure(): void
    {
        $this->setName('dump')
            ->setDescription('Dump actual database structure to migration file')
            ->addOption('data', 'd', InputOption::VALUE_NONE, 'Dump structure and also data')
            ->addOption('ignore-data-tables', null, InputOption::VALUE_REQUIRED, 'Comma separated list of tables which will be exported without data (Option -d, --data is required to use this option)')
        ;

        parent::configure();
    }

    protected function migrationDefaultName(): string
    {
        return 'Initialization';
    }

    protected function sourceStructure(): Structure
    {
        return new Structure();
    }

    protected function targetStructure(): Structure
    {
        return $this->adapter->getStructure();
    }

    protected function loadData(array $tables = []): array
    {
        if (!(bool)$this->input->getOption('data')) {
            return [];
        }

        /** @var string|null $ignoredDataTablesOption */
        $ignoredDataTablesOption = $this->input->getOption('ignore-data-tables');
        $ignoredDataTables = $ignoredDataTablesOption
            ? array_map('trim', explode(',', $ignoredDataTablesOption))
            : [];

        $data = [];
        foreach ($tables as $table) {
            if (in_array($table->getName(), $ignoredDataTables, true)) {
                continue;
            }
            $data[$table->getName()] = $this->adapter->fetchAll($table->getName());
        }
        return $data;
    }
}
