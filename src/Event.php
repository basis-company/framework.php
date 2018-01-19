<?php

namespace Basis;

use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Pool;
use ReflectionClass;

class Event
{
    private $dispatcher;
    private $filesystem;
    private $service;
    private $pool;

    public function __construct(Dispatcher $dispatcher, Service $service, Pool $pool, Filesystem $filesystem)
    {
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->service = $service;
        $this->pool = $pool;
    }

    public function getSubscription()
    {
        $subscription = [];
        foreach ($this->filesystem->listClasses('Listener') as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            foreach ($reflection->getStaticPropertyValue('events') as $event) {
                if (!array_key_exists($event, $subscription)) {
                    $subscription[$event] = [];
                }
                $subscription[$event][] = substr($class, strlen('Listener\\'));
            }
        }

        return $subscription;
    }

    public function fire(string $event, $context)
    {
        $this->dispatcher->dispatch('event.fire', [
            'event' => $this->service->getName().'.'.$event,
            'context' => $context,
        ]);
    }

    public function fireChanges(string $producer)
    {
        foreach ($this->pool->getMappers() as $mapper) {
            $spy = $mapper->getPlugin(Spy::class);
            if ($spy->hasChanges()) {
                // reduce changes list
                $changes = $spy->getChanges();
                foreach ($changes as $action => $collection) {
                    foreach ($collection as $space => $entities) {
                        $event = $this->service->getName().'.'.$space.'.'.$action;

                        if (!$this->service->eventExists($event)) {
                            unset($collection[$space]);
                        }
                    }
                    if (!count($collection)) {
                        unset($changes->$action);
                    }
                }

                if (count(get_object_vars($changes))) {
                    $this->dispatcher->dispatch('event.changes', [
                        'producer' => $producer,
                        'changes' => $changes,
                        'service' => $this->service->getName(),
                    ]);
                }

                $spy->reset();
            }
        }
    }
}
