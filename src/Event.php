<?php

namespace Basis;

use Tarantool\Mapper\Plugins\Spy;

class Event
{
    private $dispatcher;
    private $service;
    private $spy;

    public function __construct(Dispatcher $dispatcher, Service $service, Spy $spy)
    {
        $this->dispatcher = $dispatcher;
        $this->service = $service;
        $this->spy = $spy;
    }

    public function fireChanges()
    {
        if($this->spy->hasChanges()) {
            // reduce changes list
            $changes = $this->spy->getChanges();
            foreach($changes as $action => $collection) {
                foreach($collection as $space => $entities) {

                    $event = $this->service->getName().'.'.$space.'.'.$action;

                    if(!$this->service->eventExists($event)) {
                        unset($collection[$space]);
                    }
                }
                if(!count($collection)) {
                    unset($changes[$action]);
                }
            }

            $this->dispatcher->dispatch('event.changes', [
                'changes' => $spy->getChanges(),
                'service' => $this->service,
            ]);
        }
    }
}
