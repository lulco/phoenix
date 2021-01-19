<?php

namespace Phoenix\Migration;

use Phoenix\Templates\TemplateManager;

class MigrationCreator
{
    /** @var MigrationNameCreator */
    private $migrationNameCreator;

    /** @var TemplateManager */
    private $templateManager;

    public function __construct(string $migration, string $indent, ?string $templatePath = null)
    {
        $this->migrationNameCreator = new MigrationNameCreator($migration);
        $this->templateManager = new TemplateManager($this->migrationNameCreator, $indent, $templatePath);
    }

    public function create(string $up, string $down, string $migrationDir): string
    {
        $template = $this->templateManager->createMigrationFromTemplate($up, $down);
        $migrationPath = $migrationDir . '/' . $this->migrationNameCreator->getFileName();
        file_put_contents($migrationPath, $template);
        return (string)realpath($migrationPath);
    }
}
