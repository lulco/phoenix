<?php

namespace Phoenix\Migration;

class ClassNameCreator
{
    private $datetime;
    
    private $className;
    
    public function __construct($filepath)
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        if (strpos($filename, '_')) {
            list($datetime, $migrationName) = explode('_', $filename, 2);
        } else {
            $datetime = 0;
            $migrationName = $filename;
        }
        $this->datetime = $datetime;
        $this->className = $this->findNamespace($filepath) . str_replace(' ', '', ucwords(str_replace('_', ' ', $migrationName)));  // TODO find real class name not create it from filename
    }
    
    public function getClassName()
    {
        return $this->className;
    }

    public function getDatetime()
    {
        return $this->datetime;
    }

    public static function createMigrationName($fullClassName)
    {
        $className = array_pop(explode('\\', $fullClassName));
        $fileName = '';
		for ($i = 0; $i < strlen($className); $i++) {
			$char = $className[$i];
			if ($char == strtoupper($char)) {
				$fileName .= '_';
			}
			$fileName .= strtolower($char);
		}
        return date('YmdHis') . $fileName . '.php';
    }
    
    public static function createClassNameAndNamespace($fullClassName)
    {
        if (substr($fullClassName, 0, 1) == '\\') {
            $fullClassName = substr($fullClassName, 1);
        }
        $classNameParts = explode('\\', $fullClassName);
        $className = array_pop($classNameParts);
        $namespace = implode('\\', $classNameParts);
        return ['class_name' => $className, 'namespace' => $namespace];
    }
    
    private function findNamespace($filepath)
    {
        $fileContent = file_get_contents($filepath);
        
        // remove comments
        $patterns = ['/\/\*(.*?)\*\//s', "/\/\/(.*?)\n/s"];
        foreach ($patterns as $pattern) {
            $fileContent = preg_replace($pattern, '', $fileContent);
        }
        
        $pattern = '/namespace (.*?);/s';
        preg_match($pattern, $fileContent, $matches);
        $namespaceIndex = 1;
        return isset($matches[$namespaceIndex]) ? '\\' . $matches[$namespaceIndex] . '\\' : '\\';
    }
}
