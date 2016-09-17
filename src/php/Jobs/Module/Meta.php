<?php

namespace Basis\Jobs\Module;

use Basis\Filesystem;
use ReflectionClass;
use ReflectionMethod;

class Meta
{
    public function run(Filesystem $fs)
    {
        $routes = [];
        foreach($fs->listClasses('Controllers') as $class) {
            $nick = substr(strtolower($class), 12);
            $methods = (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach($methods as $method) {
                if($method->getName() != 'index') {
                    $routes[] = $nick.'/'.$method->getName();
                } else {
                    $routes[] = $nick;
                }
            }
        }

        $jobs = [];
        foreach($fs->listClasses('Jobs') as $class) {
            $jobs[] = str_replace('\\', '.', substr(strtolower($class), 5));
        }

        $js = [];
        foreach($fs->listFiles('src/js') as $file) {
            $js[] = str_replace('/', '.', substr($file, 0, -3));
        }

        return compact('routes', 'jobs', 'js');
    }
}