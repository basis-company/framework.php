<?php

namespace Basis;

class Event
{
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function fire($event, $context)
    {
        $this->dispatcher->dispatch('event.fire', [
            'event' => $event,
            'context' => $context
        ]);
    }
}
