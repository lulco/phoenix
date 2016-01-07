<?php

namespace Phoenix\Migration;

class ClassNameCreator
{
    private $datetime;
    
    private $className;
    
    public function __construct($filepath)
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        list($datetime, $migrationName) = explode('_', $filename, 2);
        $this->datetime = $datetime;
        $this->className = $this->findNamespace($filepath) . str_replace(' ', '', ucwords(str_replace('_', ' ', $migrationName)));
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
        return isset($matches[1]) ? '\\' . $matches[1] . '\\' : '\\';
    }
}
