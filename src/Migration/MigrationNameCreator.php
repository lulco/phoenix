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
        $classNameParts = explode('\\', $fullClassName);
        $className = array_pop($classNameParts);
        $namespace = implode('\\', $classNameParts);
        
        $fileName = '';
		for ($i = 0; $i < strlen($className); $i++) {
			$char = $className[$i];
			if ($char == strtoupper($char)) {
				$fileName .= '_';
			}
			$fileName .= strtolower($char);
		}
        $this->fileName = date('YmdHis') . $fileName . '.php';
        $this->className = $className;
        $this->namespace = $namespace;
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
