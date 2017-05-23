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
        foreach ($fs->listClasses('Controllers') as $class) {
            $nick = substr(strtolower($class), 12);
            $methods = (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $routes[] = $nick.'/'.$method->getName();
            }
        }

        $jobs = [];
        foreach ($fs->listClasses('Jobs') as $class) {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isAbstract()) {
                $jobs[] = str_replace('\\', '.', substr(strtolower($class), 5));
            }
        }

        return compact('routes', 'jobs');
    }
}
