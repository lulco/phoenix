<?php

namespace Phoenix\Tests\Command;

use Phoenix\Config\Parser\PhpConfigParser;
use Phoenix\Tests\Helpers\Adapter\CleanupInterface;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var array */
    protected $configuration;

    protected function setUp()
    {
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
