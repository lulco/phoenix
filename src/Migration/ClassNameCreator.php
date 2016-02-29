<?php

namespace Phoenix\Migration;

class ClassNameCreator
{
    /** @var string */
    private $datetime = '';
    
    /** @var string */
    private $className;
    
    public function __construct($filepath)
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        $migrationName = $filename;
        if (strpos($filename, '_')) {
            list($this->datetime, $migrationName) = explode('_', $filename, 2);
        }
        $this->className = $this->findClassName($filepath);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    private function findClassName($filepath)
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
        $namespace = isset($matches[$namespaceIndex]) ? '\\' . $matches[$namespaceIndex] . '\\' : '\\';
        
        $pattern = '/class (.*?) /s';
        preg_match($pattern, $fileContent, $matches);
        $classNameIndex = 1;
        $classname = isset($matches[$classNameIndex]) ? $matches[$classNameIndex] : '';
        return $namespace . $classname;
    }
}
