<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Job;
use Swoole\Coroutine\System;

class Register extends Job
{
    public function run(Event $event)
    {
        $meta = $this->dispatch('module.meta');

        foreach ($event->getSubscription() as $name => $listeners) {
            $event->subscribe($name);
        }

        $assets = $this->dispatch('module.assets');
        $version = $this->dispatch('module.version');

        $address = $this->dispatch('resolve.address', [
            'name' => gethostname(),
        ]);

        $this->dispatch('web.register', [
            'service' => $this->app->getName(),
            'hash' => $assets->hash,
            'routes' => $meta->routes,
            'host' => $address->host,
            'version' => $version->version,
        ]);
    }
}
