<?php

namespace Basis\Job\Module;

use Basis\Config;
use Basis\Filesystem;
use ReflectionClass;
use ReflectionMethod;

class Meta
{
    public function run(Filesystem $fs, Config $config)
    {
        $routes = [];
        foreach ($fs->listClasses('Controller') as $class) {
            $nick = substr(strtolower($class), 11);
            $methods = (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $routes[] = $nick.'/'.$method->getName();
            }
        }

        $jobs = [];
        foreach ($fs->listClasses('Job') as $class) {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isAbstract()) {
                $jobs[] = str_replace('\\', '.', substr(strtolower($class), 4));
            }
        }

        return compact('routes', 'jobs');
    }
}
