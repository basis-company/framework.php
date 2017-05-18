<?php

namespace Basis\Jobs\Module;

use Basis\Converter;
use Basis\Filesystem;
use Basis\Service;
use Basis\Runner;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class Bootstrap
{
    public function run(Runner $runner, Service $service, Filesystem $fs, Converter $converter)
    {
        $runner->dispatch('tarantool.migrate');

        $meta = $runner->dispatch('module.meta');
        foreach ($meta['jobs'] as $job) {
            $class = new ReflectionClass($runner->getJobClass($job));
            $params = [];
            foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $params[] = $property->getName();
            }
            $service->registerJob($job, $params);
        }

        foreach ($fs->listClasses('Listeners') as $class) {
            $chain = str_replace('\\', '.', substr($class, 10));

            foreach ($chain as $k => $v) {
                $chain[$k] = $converter->toCamelCase($v);
            }
            $event = implode('.', $event);

            $service->subscribe($event);
        }
    }
}
