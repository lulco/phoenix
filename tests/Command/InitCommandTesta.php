<?php

namespace Phoenix\Tests\Config;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Config\Parser\PhpConfigParser;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\WrongCommandException;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommandTest extends PHPUnit_Framework_TestCase
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
        $command = new InitCommand();
        $this->assertEquals('init', $command->getName());
    }

    public function testCustomName()
    {
        $command = new InitCommand('my_init');
        $this->assertEquals('my_init', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new InitCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new InitCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new InitCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertCount(2, $messages[OutputInterface::VERBOSITY_DEBUG]);
    }

    public function testSetCustomConfig()
    {
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertCount(2, $messages[OutputInterface::VERBOSITY_DEBUG]);
    }

    public function testMultipleInitialization()
    {
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $this->setExpectedException(WrongCommandException::class, 'Phoenix was already initialized, run migrate or rollback command now.');
        $command->run($this->input, $this->output);
    }
}
