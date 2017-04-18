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

    public function exists()
    {
        $path = call_user_func_array([$this, 'getPath'], func_get_args());
        return is_dir($path) || file_exists($path);
    }

    public function getPath()
    {
        if (func_get_args()) {
            $chain = func_get_args();
            array_unshift($chain, $this->root);
            foreach ($chain as $k => $v) {
                if (!strlen($v)) {
                    unset($chain[$k]);
                }
            }
            return implode(DIRECTORY_SEPARATOR, array_values($chain));
        }

        return $this->root;
    }

    public function listClasses($namespace = '')
    {
        $location = "src";

        if($namespace) {
            $location .= '/'.str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }

        $files = $this->listFiles($location);
        $classes = [];

        $namespace = $this->completeClassName($namespace);

        foreach($files as $file) {
            $class = str_replace(['\\', '/'], '\\', $file);
            $class = substr($class, 0, -4);
            if($namespace) {
                $class = $namespace.'\\'.$class;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    public function listFiles($location)
    {
        $absolute = $this->getPath($location);
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

    public function completeClassName($classname)
    {
        if($this->namespace && $classname) {
            return $this->namespace.'\\'.$classname;
        }
        return $classname;
    }

}
