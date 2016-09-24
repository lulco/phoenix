<?php

namespace Phoenix\Tests\Config;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\StatusCommand;
use Phoenix\Config\Parser\NeonConfigParser;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Mock\Command\Formatter;
use Phoenix\Tests\Mock\Command\Input;
use Phoenix\Tests\Mock\Command\Output;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommandTest extends PHPUnit_Framework_TestCase
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
        $this->output->setFormatter(new Formatter());
    }

    public function testDefaultName()
    {
        $command = new StatusCommand();
        $this->assertEquals('status', $command->getName());
    }

    public function testCustomName()
    {
        $command = new StatusCommand('my_status');
        $this->assertEquals('my_status', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new StatusCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new StatusCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new StatusCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.neon');
        $command->run($this->input, $this->output);
    }

    public function testStatusWithoutInitializing()
    {
        $command = new StatusCommand();
        $this->input->setOption('config', __DIR__ . '/../../example/phoenix.neon');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertEquals('<comment>Executed migrations</comment>' . "\n", $messages[0][6]);
        $this->assertEquals('<info>No executed migrations</info>' . "\n", $messages[0][7]);
        $this->assertEquals('<comment>Migrations to execute</comment>' . "\n", $messages[0][9]);
        $this->assertEquals('|<info> Class name </info>|' . "\n", $messages[0][11]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testStatusWithInitializing()
    {
        $input = new Input();
        $output = new Output();
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $command = new StatusCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<comment>Executed migrations</comment>' . "\n", $messages[0][1]);
        $this->assertEquals('<info>No executed migrations</info>' . "\n", $messages[0][2]);
        $this->assertEquals('<comment>Migrations to execute</comment>' . "\n", $messages[0][4]);
        $this->assertEquals('|<info> Class name </info>|' . "\n", $messages[0][6]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testStatusAfterOneExecutedMigration()
    {
        $input = new Input();
        $input->setOption('first', true);
        $output = new Output();
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $command = new StatusCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<comment>Executed migrations</comment>' . "\n", $messages[0][1]);
        $this->assertEquals('|<info> Class name </info>|<info> Executed at </info>|' . "\n", $messages[0][3]);
        $this->assertEquals('<comment>Migrations to execute</comment>' . "\n", $messages[0][8]);
        $this->assertEquals('|<info> Class name </info>|' . "\n", $messages[0][10]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testStatusAfterAllMigrationsExecuted()
    {
        $input = new Input();
        $output = new Output();
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $input = new Input();
        $output = new Output();
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $executedMigrationsCount = (count($output->getMessages(0)) - 3) / 3;

        $command = new StatusCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<comment>Executed migrations</comment>' . "\n", $messages[0][1]);
        $this->assertEquals('|<info> Class name </info>|<info> Executed at </info>|' . "\n", $messages[0][3]);
        $this->assertEquals('<comment>Migrations to execute</comment>' . "\n", $messages[0][7 + $executedMigrationsCount]);
        $this->assertEquals('<info>No migrations to execute</info>' . "\n", $messages[0][8 + $executedMigrationsCount]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }
}
