<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
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
    public function run(Runner $runner, Dispatcher $dispatcher, Service $service,
                        Event $event, Framework $framework, Filesystem $fs)
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
        $dispatcher->dispatch('asset.register', [
            'service' => $service->getName(),
            'hash' => $assets['hash'],
        ]);

        foreach ($framework->listFiles('resources/default') as $file) {
            if (!$fs->exists($file)) {
                $source = $framework->getPath("resources/default/$file");
                $destination = $fs->getPath($file);
                file_put_contents($destination, file_get_contents($source));
            }
        }
    }
}
