<?php

declare(strict_types=1);

namespace Phoenix\Migration;

final class ClassNameCreator
{
    private string $datetime = '';

    private string $className = '';

    public function __construct(string $filepath)
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        if (strpos($filename, '_')) {
            $filenameParts = explode('_', $filename, 2);
            $this->datetime = $filenameParts[0];
        }
        $this->className = $this->findClassName($filepath);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getDatetime(): string
    {
        return $this->datetime;
    }

    private function findClassName(string $filepath): string
    {
        $fileContent = (string)file_get_contents($filepath);

        // remove comments
        $patterns = ['/\/\*(.*?)\*\//s', "/\/\/(.*?)\n/s"];
        foreach ($patterns as $pattern) {
            $fileContent = (string)preg_replace($pattern, '', $fileContent);
        }

        $pattern = '/namespace (.*?);/s';
        preg_match($pattern, $fileContent, $matches);
        $namespaceIndex = 1;
        $namespace = isset($matches[$namespaceIndex]) ? '\\' . $matches[$namespaceIndex] . '\\' : '\\';

        $pattern = '/class (.*?) /s';
        preg_match($pattern, $fileContent, $matches);
        $classNameIndex = 1;
        $classname = isset($matches[$classNameIndex]) ? $matches[$classNameIndex] : '';
        return $namespace . $classname;
    }
}
