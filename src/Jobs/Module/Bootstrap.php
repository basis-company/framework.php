<?php

namespace Basis\Jobs\Module;

use Basis\Filesystem;
use Basis\Etcd;
use Basis\Runner;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class Bootstrap
{
    public function run(Runner $runner, Etcd $etcd, Filesystem $fs)
    {
        $runner->dispatch('tarantool.migrate');

        $etcd->registerService();

        $meta = $runner->dispatch('module.meta');
        foreach($meta['jobs'] as $job) {
            $class = new ReflectionClass($runner->getJobClass($job));
            $params = [];
            foreach($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $params[] = $property->getName();
            }
            $etcd->registerJob($job, $params);
        }

        foreach($fs->listClasses('Listeners') as $class) {
            $event = str_replace('\\', '.', substr(strtolower($class), 10));
            $etcd->subscribe($event);
        }
    }
}
