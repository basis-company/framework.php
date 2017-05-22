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
    public function run(Runner $runner, Service $service, Event $event)
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

        foreach ($event->getSubscription() as $event => $listeners) {
            $service->subscribe($event);
        }

        $assets = $runner->dispatch('module.assets');
        $service->updateAssetsVersion($assets['hash']);
    }
}
