<?php

namespace Phoenix\Tests\Config;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Config\Parser\NeonConfigParser;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommandTest extends PHPUnit_Framework_TestCase
{
    private $input;

    private $output;

    private $configuration;

    protected function setUp()
    {
        $parser = new NeonConfigParser();
        $this->configuration = $parser->parse(__DIR__ . '/../../example/phoenix.neon');

        $cleanup = new CleanupCommand();
        $cleanup->setConfig($this->configuration);
        $cleanup->run(new Input(), new Output());

        $this->input = new Input();
        $this->output = new Output();
    }

    public function testDefaultName()
    {
        $command = new MigrateCommand();
        $this->assertEquals('migrate', $command->getName());
    }

    public function testCustomName()
    {
        $command = new MigrateCommand('my_migrate');
        $this->assertEquals('my_migrate', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new MigrateCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new MigrateCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new MigrateCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.neon');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testSetCustomConfig()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testMultipleMigration()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = new Input();
        $output = new Output();
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to migrate</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testOnlyFirstMigration()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);

        $input = new Input();
        $input->setOption('first', true);
        $output = new Output();
        $command->run($input, $output);
        $messagesFirst = $output->getMessages();

        $command = new CleanupCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $input = new Input();
        $output = new Output();
        $command->run($input, $output);
        $messagesAll = $output->getMessages();

        $this->assertGreaterThan(count($messagesFirst[0]), count($messagesAll[0]));
    }
}
