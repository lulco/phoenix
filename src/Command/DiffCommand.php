<?php

namespace Phoenix\Command;

use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Element\Structure;
use Phoenix\Exception\InvalidArgumentValueException;
use Symfony\Component\Console\Input\InputArgument;

class DiffCommand extends AbstractDumpCommand
{
    protected function configure(): void
    {
        $this->setName('diff')
            ->setDescription('Makes diff of source and target database')
            ->addArgument('source', InputArgument::REQUIRED, 'Source environment from config')
            ->addArgument('target', InputArgument::REQUIRED, 'Target environment from config')
        ;

        parent::configure();
    }

    protected function sourceStructure(): Structure
    {
        return $this->getStructure('source');
    }

    protected function targetStructure(): Structure
    {
        return $this->getStructure('target');
    }

    protected function loadData(array $tables): array
    {
        return [];
    }

    private function getStructure(string $type): Structure
    {
        $env = $this->input->getArgument($type);
        $config = $this->config->getEnvironmentConfig($env);
        if (!$config) {
            throw new InvalidArgumentValueException(ucfirst($type) . ' environment "' . $env . '" doesn\'t exist in config');
        }

        $adapter = AdapterFactory::instance($config);
        return $adapter->getStructure();
    }
}
