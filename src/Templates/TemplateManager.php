<?php

namespace Phoenix\Templates;

use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;

class TemplateManager
{
    /** @var MigrationNameCreator */
    private $migrationNameCreator;

    /** @var string */
    private $indent;

    /** @var string */
    private $templatePath;

    public function __construct(MigrationNameCreator $migrationNameCreator, string $indent, ?string $templatePath = null)
    {
        $this->migrationNameCreator = $migrationNameCreator;
        $this->indent = $indent;
        $templatePath = $templatePath ?: __DIR__ . '/DefaultTemplate.phoenix';
        if (!is_file($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" not found');
        }
        $this->templatePath = $templatePath;
    }

    public function createMigrationFromTemplate(string $up, string $down): string
    {
        $template = (string)file_get_contents($this->templatePath);
        $namespace = '';
        if ($this->migrationNameCreator->getNamespace()) {
            $namespace .= "namespace {$this->migrationNameCreator->getNamespace()};\n\n";
        }
        $template = str_replace('###NAMESPACE###', $namespace, $template);
        $template = str_replace('###CLASSNAME###', $this->migrationNameCreator->getClassName(), $template);
        $template = str_replace('###INDENT###', $this->indent, $template);
        $template = str_replace('###UP###', $up, $template);
        $template = str_replace('###DOWN###', $down, $template);
        return $template;
    }
}
