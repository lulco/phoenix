<?php

namespace Phoenix\Tests\Config;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Phoenix\Config\Parser\PhpConfigParser;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommandTest extends PHPUnit_Framework_TestCase
{
    private $input;

    private $output;

    private $configuration;

    protected function setUp()
    {
        $parser = new PhpConfigParser();
        $this->configuration = $parser->parse(__DIR__ . '/../../example/phoenix.php');

        $cleanup = new CleanupCommand();
        $cleanup->setConfig($this->configuration);
        $cleanup->run(new Input(), new Output());

        $this->input = new Input();
        $this->output = new Output();
    }

    public function testDefaultName()
    {
        $command = new RollbackCommand();
        $this->assertEquals('rollback', $command->getName());
    }

    public function testCustomName()
    {
        $command = new RollbackCommand('my_rollback');
        $this->assertEquals('my_rollback', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new RollbackCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.php');
        $command->run($this->input, $this->output);
    }

    public function testNothingToRollbackWithoutInitializing()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][6]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testNothingToRollbackWithInitializing()
    {
        $input = new Input();
        $output = new Output();
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testMigrateAndRollback()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = new Input();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(6, $messages[0]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        $input = new Input();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(6, $messages[0]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testRollbackAll()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = new Input();
        $input->setOption('all', true);
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        $input = new Input();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testDryRun()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = new Input();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $input = new Input();
        $input->setOption('dry', true);
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $dryMessages = $output->getMessages();
        $dryQueries = array_slice($dryMessages[0], 3, -3);

        $this->assertTrue(is_array($dryMessages));
        $this->assertArrayHasKey(0, $dryMessages);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $dryMessages);

        $input = new Input();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertEquals($dryQueries, $messages[OutputInterface::VERBOSITY_DEBUG]);
    }
}
