<?php

namespace Basis;

use Basis\Dispatcher;
use Exception;
use ReflectionClass;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Pool;

class Event
{
    use Toolkit;

    public function getSubscription()
    {
        $subscription = [];
        foreach ($this->get(Filesystem::class)->listClasses('Listener') as $class) {
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
            'event'   => $this->get(Service::class)->getName().'.'.$event,
            'context' => $context,
        ]);
    }

    public function fireChangesPart(string $producer, int $fraction = 10)
    {
        if (++$this->counter % $fraction === 0) {
            return $this->fireChanges($producer);
        }
        return false;
    }

    public function hasChanges()
    {
        $hasChanges = false;

        $serviceName = $this->get(Service::class)->getName();
        $this->get(Pool::class)->get($serviceName);

        foreach ($this->get(Pool::class)->getMappers() as $mapper) {
            if ($mapper->getPlugin(Spy::class)->hasChanges()) {
                $hasChanges = true;
            }
        }
        return $hasChanges;
    }

    public function fireChanges(string $producer)
    {
        $this->get(Pool::class)->get($this->get(Service::class)->getName());

        $dispatcher = $this->app->get(Dispatcher::class);
        $changed = false;

        foreach ($this->get(Pool::class)->getMappers() as $mapper) {
            $spy = $mapper->getPlugin(Spy::class);
            if ($spy->hasChanges()) {
                // reduce changes list
                $changes = $spy->getChanges();
                foreach ($changes as $action => $collection) {
                    foreach ($collection as $space => $entities) {
                        $event = $this->get(Service::class)->getName().'.'.$space.'.'.$action;

                        if (!$this->get(Service::class)->eventExists($event)) {
                            unset($collection[$space]);
                        }
                    }
                    if (!count($collection)) {
                        unset($changes->$action);
                    }
                }

                if (count(get_object_vars($changes))) {
                    $changed = true;
                    $data = $this->get(Converter::class)->toArray([
                        'changes'  => $changes,
                        'producer' => $producer,
                        'service'  => $mapper->serviceName,
                        'context' => $this->get(Context::class),
                    ]);
                    try {
                        // put changes to queue
                        $this->getQueue('event.changes')->put($data);
                    } catch (Exception $e) {
                        // use legacy https transport
                        // todo split data into chunks
                        $dispatcher->send('event.changes', $data);
                    }
                }

                $spy->reset();
            }
        }
        return $changed;
    }
}
