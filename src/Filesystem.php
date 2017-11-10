<?php

namespace Basis;

class Filesystem
{
    private $app;
    protected $root;
    protected $namespace;

    public function __construct(Application $app, $root)
    {
        $this->app = $app;
        $this->root = $root;
    }

    public function exists(...$args) : bool
    {
        $path = $this->getPath(...$args);
        return is_dir($path) || file_exists($path);
    }

    public function getPath(...$args) : string
    {
        if (count($args)) {
            array_unshift($args, $this->root);
            foreach ($args as $k => $v) {
                if (!strlen($v)) {
                    unset($args[$k]);
                }
            }
            return implode(DIRECTORY_SEPARATOR, array_values($args));
        }

        return $this->root;
    }

    public function listClasses(string $namespace = '', string $location = 'php') : array
    {
        if ($namespace) {
            $location .= '/'.str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }

        $files = $this->listFiles($location);
        $classes = [];

        $namespace = $this->completeClassName($namespace);

        foreach ($files as $file) {
            $class = str_replace(['\\', '/'], '\\', $file);
            $class = substr($class, 0, -4);
            if ($namespace) {
                $class = $namespace.'\\'.$class;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    public function listFiles(...$args) : array
    {
        $absolute = $this->getPath(...$args);
        if (!is_dir($absolute)) {
            return [];
        }

        $result = [];
        $relative = substr($absolute, strlen($this->getPath()));
        foreach (scandir($absolute) as $file) {
            if ($file != '.' && $file != '..') {
                if (is_file("$absolute/$file")) {
                    $result[] = $file;
                } else {
                    foreach ($this->listFiles("$relative/$file") as $child) {
                        $result[] = "$file/$child";
                    }
                }
            }
        }

        return $result;
    }

    public function completeClassName(string $classname) : string
    {
        if ($this->namespace && $classname) {
            return $this->namespace.'\\'.$classname;
        }
        return $classname;
    }
}
