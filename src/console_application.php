<?php

declare(strict_types=1);

$autoloader = require __DIR__ . '/composer_autoloader.php';

if (!$autoloader()) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\CreateCommand;
use Phoenix\Command\DiffCommand;
use Phoenix\Command\DumpCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Phoenix\Command\StatusCommand;
use Phoenix\Command\TestCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new CreateCommand());
$application->add(new InitCommand());
$application->add(new CleanupCommand());
$application->add(new MigrateCommand());
$application->add(new RollbackCommand());
$application->add(new StatusCommand());
$application->add(new DumpCommand());
$application->add(new DiffCommand());
$application->add(new TestCommand());
return $application;
