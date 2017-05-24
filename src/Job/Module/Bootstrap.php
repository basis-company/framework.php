<?php

namespace Basis\Job\Module;

use Basis\Converter;
use Basis\Event;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Runner;
use Basis\Service;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class Bootstrap
{
    public function run(Runner $runner, Service $service, Event $event, Framework $framework, Filesystem $fs)
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

        foreach ($meta['routes'] as $route) {
            $service->registerRoute($route);
        }

        foreach ($event->getSubscription() as $event => $listeners) {
            $service->subscribe($event);
        }

        $assets = $runner->dispatch('module.assets');
        $service->updateAssetsVersion($assets['hash']);

        foreach ($framework->listFiles('resources/defaults') as $file) {
            if (!$fs->exists($file)) {
                $source = $framework->getPath("resources/defaults/$file");
                $destination = $fs->getPath($file);
                file_put_contents($destination, file_get_contents($source));
            }
        }
    }
}
