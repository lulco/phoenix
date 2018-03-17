<?php

namespace Phoenix\Tests\Command;

use Phoenix\Config\Parser\PhpConfigParser;
use Phoenix\Tests\Helpers\Adapter\CleanupInterface;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

abstract class BaseCommandTest extends TestCase
{
    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var array */
    protected $configuration;

    protected function setUp()
    {
        // delete root config
        $rootConfigPath = __DIR__ . '/../../phoenix.php';
        if (file_exists($rootConfigPath)) {
            unlink($rootConfigPath);
        }

        // delete additional migration dir
        $newMigrationDir = __DIR__ . '/../../testing_migrations/new';
        if (file_exists($newMigrationDir)) {
            $newFiles = Finder::create()->files()->in($newMigrationDir);
            foreach ($newFiles as $newFile) {
                $filePath = (string)$newFile;
                unlink($filePath);
            }
            rmdir($newMigrationDir);
        }

        $adapter = $this->getAdapter();
        $adapter->cleanupDatabase();

        $parser = new PhpConfigParser();
        $this->configuration = $parser->parse(__DIR__ . '/../../testing_migrations/config/phoenix.php');

        $this->input = $this->createInput();
        $this->output = new Output();
    }

    /**
     * @return InputInterface
     */
    protected function createInput()
    {
        $input = new Input();
        $input->setOption('environment', $this->getEnvironment());
        return $input;
    }

    /**
     * @return string
     */
    abstract protected function getEnvironment();

    /**
     * @return CleanupInterface
     */
    abstract protected function getAdapter();
}
