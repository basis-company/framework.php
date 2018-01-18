<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Job;
use Basis\Service;

class Register extends Job
{
    public function run(Event $event, Service $service)
    {
        $meta = $this->dispatch('module.meta');

        foreach ($event->getSubscription() as $name => $listeners) {
            $service->subscribe($name);
        }

        $assets = $this->dispatch('module.assets');

        $registration = [
            'service' => $service->getName(),
            'hash' => $assets['hash'],
            'routes' => $meta['routes'],
        ];

        $this->app->dispatch('web.register', $registration, $service->getName());
    }
}
