<?php

namespace Phoenix\Migration;

class ClassNameCreator
{
    private $datetime = 0;
    
    private $className;
    
    public function __construct($filepath)
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        $migrationName = $filename;
        if (strpos($filename, '_')) {
            list($this->datetime, $migrationName) = explode('_', $filename, 2);
        }
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
