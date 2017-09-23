<?php

namespace Phoenix\Migration;

class MigrationNameCreator
{
    private $namespace;

    private $className;

    private $fileName;

    public function __construct($fullClassName)
    {
        if (substr($fullClassName, 0, 1) == '\\') {
            $fullClassName = substr($fullClassName, 1);
        }
        $classNameParts = array_map('ucfirst', explode('\\', $fullClassName));
        $className = array_pop($classNameParts);
        $namespace = implode('\\', $classNameParts);

        $this->fileName = $this->createFileName($className);
        $this->className = $className;
        $this->namespace = $namespace;
    }

    private function createFileName($className)
    {
        $fileName = '';
        $length = strlen($className);
        for ($i = 0; $i < $length; $i++) {
            $char = $className[$i];
            if ($char == strtoupper($char)) {
                $fileName .= '_';
            }
            $fileName .= strtolower($char);
        }
        return date('YmdHis') . $fileName . '.php';
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }
}
