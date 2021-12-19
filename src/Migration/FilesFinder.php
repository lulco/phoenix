<?php

declare(strict_types=1);

namespace Phoenix\Migration;

use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

final class FilesFinder
{
    /** @var string[] */
    private array $directories = [];

    public function addDirectory(string $path): FilesFinder
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

    public function removeDirectory(string $path): FilesFinder
    {
        $index = array_search($path, $this->directories);
        if ($index === false) {
            throw new InvalidArgumentException('"Path ' . $path . '" was not found in list of directories');
        }

        unset($this->directories[$index]);
        return $this;
    }

    public function removeDirectories(): FilesFinder
    {
        $this->directories = [];
        return $this;
    }

    /**
     * @return string[]
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        $files = [];
        foreach ($this->directories as $directory) {
            $phpFiles = Finder::create()->files()->name('*.php')->in($directory);
            foreach ($phpFiles as $file) {
                $files[] = (string) $file;
            }
        }
        return $files;
    }
}
