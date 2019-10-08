<?php

namespace Basis\Job\Module;

use Basis\Filesystem;
use Basis\Job;
use ReflectionClass;
use ReflectionMethod;

class Meta extends Job
{
    public function run(Filesystem $fs)
    {
        $routes = [];
        foreach ($fs->listClasses('Controller') as $class) {
            $nick = substr(strtolower($class), 11);
            $reflectionClass =new ReflectionClass($class);
            $traits = $reflectionClass->getTraits();
            $ignore = [];
            if (count($traits)) {
                foreach ($traits as $trait) {
                    foreach ($trait->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->isConstructor() || $method->getName() == '__debugInfo') {
                            continue;
                        }
                        $ignore[] = $method->getName();
                    }
                }
            }
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->isConstructor() || $method->getName() == '__debugInfo') {
                    continue;
                }
                if (in_array($method->name, $ignore)) {
                    continue;
                }
                if ($method->name == '__process') {
                    $routes[] = $nick.'/*';
                } else {
                    $routes[] = $nick.'/'.$method->name;
                }
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
