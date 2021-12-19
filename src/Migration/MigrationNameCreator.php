<?php

declare(strict_types=1);

namespace Phoenix\Migration;

final class MigrationNameCreator
{
    private string $namespace;

    private string $className;

    private string $fileName;

    public function __construct(string $fullClassName)
    {
        if (substr($fullClassName, 0, 1) === '\\') {
            $fullClassName = substr($fullClassName, 1);
        }
        $classNameParts = array_map('ucfirst', explode('\\', $fullClassName));
        $className = array_pop($classNameParts);
        $namespace = implode('\\', $classNameParts);

        $this->fileName = $this->createFileName($className);
        $this->className = $className;
        $this->namespace = $namespace;
    }

    private function createFileName(string $className): string
    {
        $fileName = '';
        $length = strlen($className);
        for ($i = 0; $i < $length; $i++) {
            $char = $className[$i];
            if ($char === strtoupper($char)) {
                $fileName .= '_';
            }
            $fileName .= strtolower($char);
        }
        return date('YmdHis') . $fileName . '.php';
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }
}
