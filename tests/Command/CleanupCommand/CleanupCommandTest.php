<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command\CleanupCommand;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CleanupCommandTest extends BaseCommandTest
{
    public function testDefaultName(): void
    {
        $command = new CleanupCommand();
        $this->assertEquals('cleanup', $command->getName());
    }

    public function testCustomName(): void
    {
        $command = new CleanupCommand('my_cleanup');
        $this->assertEquals('my_cleanup', $command->getName());
    }

    public function testMissingDefaultConfig(): void
    {
        $command = new CleanupCommand();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound(): void
    {
        $command = new CleanupCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile(): void
    {
        $initCommand = new InitCommand();
        $input = $this->createInput();
        $input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $initCommand->run($input, new Output());

        $command = new CleanupCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(6, $messages[0]);
        $this->assertEquals('<info>Phoenix cleaned</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertTrue(count($messages[OutputInterface::VERBOSITY_DEBUG]) > 0);
    }

    public function testUserConfigFileAndJsonOutput(): void
    {
        $initCommand = new InitCommand();
        $input = $this->createInput();
        $input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $initCommand->run($input, new Output());

        $command = new CleanupCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $this->input->setOption('output-format', 'json');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages(0);

        $this->assertTrue(is_array($messages));
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertJson($messages[0]);

        $message = json_decode($messages[0], true);

        $this->assertArrayHasKey('message', $message);
        $this->assertEquals('Phoenix cleaned', $message['message']);
        $this->assertArrayNotHasKey('executed_migrations', $message);
        $this->assertArrayHasKey('execution_time', $message);
    }

    public function testUserConfigFileAndJsonOutputAndDebugVerbosity(): void
    {
        $initCommand = new InitCommand();
        $input = $this->createInput();
        $input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $initCommand->run($input, new Output());

        $command = new CleanupCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $this->input->setOption('output-format', 'json');
        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages(0);

        $this->assertTrue(is_array($messages));
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertJson($messages[0]);

        $message = json_decode($messages[0], true);

        $this->assertArrayHasKey('message', $message);
        $this->assertEquals('Phoenix cleaned', $message['message']);
        $this->assertArrayHasKey('executed_migrations', $message);
        $this->assertArrayHasKey('execution_time', $message);
    }
}
