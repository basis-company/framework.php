<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
use Basis\Event;
use Basis\Runner;
use Basis\Service;

class Register
{
    public function run(Runner $runner, Dispatcher $dispatcher, Service $service, Event $event)
    {
        $service->register();

        $runner->dispatch('tarantool.migrate');

        $meta = $runner->dispatch('module.meta');
        foreach ($meta['routes'] as $route) {
            $service->registerRoute($route);
        }

        foreach ($event->getSubscription() as $event => $listeners) {
            $service->subscribe($event);
        }

        $assets = $runner->dispatch('module.assets');

        ($service->getName() == 'web' ? $runner : $dispatcher)
            ->dispatch('asset.register', [
                'service' => $service->getName(),
                'hash' => $assets['hash'],
            ]);
    }
}
