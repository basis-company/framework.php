<?php

namespace Basis;

use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Pool;
use ReflectionClass;

class Event
{
    private $app;
    private $filesystem;
    private $service;
    private $pool;

    public function __construct(Application $app, Service $service, Pool $pool, Filesystem $filesystem)
    {
        $this->app = $app;
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
        $this->app->dispatch('event.fire', [
            'event' => $this->service->getName().'.'.$event,
            'context' => $context,
        ]);
    }

    public function fireChanges(string $producer)
    {
        $this->pool->get($this->service->getName());

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
                    $this->app->dispatch('event.changes', [
                        'producer' => $producer,
                        'changes' => $changes,
                        'service' => $mapper->serviceName,
                    ]);
                }

                $spy->reset();
            }
        }
    }
}
