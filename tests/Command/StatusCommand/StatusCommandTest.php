<?php

namespace Phoenix\Tests\Command\StatusCommand;

use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\StatusCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Helpers\Command\Formatter;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;

abstract class StatusCommandTest extends BaseCommandTest
{
    protected function setUp()
    {
        parent::setUp();
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
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);
    }

    public function testStatusWithoutInitializing()
    {
        $command = new StatusCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
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
        $input = $this->createInput();
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
        $input = $this->createInput();
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
        $input = $this->createInput();
        $output = new Output();
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $input = $this->createInput();
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
