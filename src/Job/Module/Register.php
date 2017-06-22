<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Job;
use Basis\Service;

class Register extends Job
{
    public function run(Service $service, Event $event)
    {
        $service->register();

        $meta = $this->dispatch('module.meta');
        foreach ($meta['routes'] as $route) {
            $service->registerRoute($route);
        }

        foreach ($event->getSubscription() as $event => $listeners) {
            $service->subscribe($event);
        }

        $assets = $this->dispatch('module.assets');

        $registration = [
            'service' => $service->getName(),
            'hash' => $assets['hash'],
        ];

        $target = $service->getName() == 'web' ? null : $service->getName();
        $this->dispatch('web.register', $registration, $target);
    }
}
