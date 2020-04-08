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
        $source = $this->input->getArgument('source');
        $sourceConfig = $this->config->getEnvironmentConfig($source);
        if (!$sourceConfig) {
            throw new InvalidArgumentValueException('Source "' . $source . '" doesn\'t exist in config');
        }

        $sourceAdapter = AdapterFactory::instance($sourceConfig);
        return $sourceAdapter->getStructure();
    }

    protected function targetStructure(): Structure
    {
        $target = $this->input->getArgument('target');
        $targetConfig = $this->config->getEnvironmentConfig($target);
        if (!$targetConfig) {
            throw new InvalidArgumentValueException('Target "' . $target . '" doesn\'t exist in config');
        }

        $targetAdapter = AdapterFactory::instance($targetConfig);
        return $targetAdapter->getStructure();
    }

    protected function shouldLoadData(): bool
    {
        return false;
    }

    protected function loadData(array $tables): array
    {
        return [];
    }
}
