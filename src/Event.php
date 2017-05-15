<?php

namespace Basis;

use Tarantool\Mapper\Plugins\Spy;

class Event
{
    private $config;
    private $dispatcher;

    public function __construct(Config $config, Dispatcher $dispatcher)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
    }

    public function fire($event, $context)
    {
        $this->dispatcher->dispatch('event.fire', [
            'event' => $event,
            'context' => $context
        ]);
    }

    public function fireChanges(Spy $spy)
    {
        if($spy->hasChanges()) {
            $this->dispatcher->dispatch('event.changes', [
                'changes' => $spy->getChanges(),
                'service' => $this->config['name'],
            ]);
        }
    }

    public function subscribe($event, $listener)
    {
        $this->dispatcher->dispatch('event.subscribe', [
            'event' => $event,
            'listener' => $listener,
        ]);
    }

    public function unsubscribe($event, $listener)
    {
        $this->dispatcher->dispatch('event.unsubscribe', [
            'event' => $event,
            'listener' => $listener,
        ]);
    }
}
