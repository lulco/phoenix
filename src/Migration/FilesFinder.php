<?php

namespace Phoenix\Migration;

use InvalidArgumentException;
use Nette\Utils\Finder;

class FilesFinder
{
    private $directories = [];
    
    public function addDirectory($path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('Directory "' . $path . '" not found');
        }
        
        if (!is_dir($path)) {
            throw new InvalidArgumentException('"' . $path . '" is not directory');
        }
        
        $index = array_search($path, $this->directories);
        if ($index === false) {
            $this->directories[] = $path;
        }
        return $this;
    }
    
    public function removeDirectory($path)
    {
        $index = array_search($path, $this->directories);
        if ($index === false) {
            throw new InvalidArgumentException('"Path ' . $path . '" was not found in list of directories');
        }
        
        unset($this->directories[$index]);
        return $this;
    }
    
    public function removeDirectories()
    {
        $this->directories = [];
        return $this;
    }
    
    public function getDirectories()
    {
        return $this->directories;
    }
    
    public function getFiles()
    {
        $files = [];
        foreach ($this->directories as $directory) {
            $phpFiles = Finder::findFiles('*.php')->in($directory);
            foreach ($phpFiles as $file) {
                $files[] = (string) $file;
            }
        }
        return $files;
    }
}
