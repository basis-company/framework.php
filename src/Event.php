<?php

namespace Basis;

use Tarantool\Mapper\Plugins\Spy;

class Event
{
    private $dispatcher;
    private $service;

    public function __construct(Dispatcher $dispatcher, Config $config)
    {
        $this->dispatcher = $dispatcher;
        $this->service = $config['service'];
        if(!$this->service) {
            throw new Exception("No service defined in config");
        }
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
                'service' => $this->service,
            ]);
        }
    }
}
