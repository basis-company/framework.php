<?php

namespace Basis;

use Basis\Container;
use Basis\Dispatcher;
use Basis\Registry;
use Exception;
use ReflectionClass;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Pool;

class Event
{
    use Toolkit;

    protected array $eventExistence = [];
    protected int $counter = 0;

    public function exists(string $event): bool
    {
        if (array_key_exists($event, $this->eventExistence)) {
            return $this->eventExistence[$event];
        }
        $types = $this->app->get(Pool::class)->get('event')->find('type');

        foreach ($types as $type) {
            if (!$type->ignore && $this->match($event, $type->nick)) {
                return $this->eventExistence[$event] = true;
            }
        }

        return $this->eventExistence[$event] = false;
    }

    public function match(string $event, string $spec): bool
    {
        if ($spec == $event) {
            return true;
        } elseif ($spec === '*.*.*') {
            return true;
        } elseif (strpos($spec, '*') !== false) {
            $spec = explode('.', $spec);
            $event = explode('.', $event);
            $valid = true;
            foreach (range(0, 2) as $part) {
                $valid = $valid && ($spec[$part] == '*' || $spec[$part] == $event[$part]);
            }
            return $valid;
        }
        return false;
    }

    public function unsubscribe(string $event)
    {
        $this->send('event.unsubscribe', [
            'event' => $event,
            'service' => $this->app->getName(),
        ]);
    }

    public function subscribe(string $event)
    {
        $this->send('event.subscribe', [
            'event' => $event,
            'service' => $this->app->getName(),
        ]);
    }

    public function getSubscription()
    {
        $registry = $this->get(Registry::class);
        $subscription = [];
        foreach ($registry->listClasses('listener') as $class) {
            if ($registry->isAbstract($class)) {
                continue;
            }
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
        $this->send('event.fire', [
            'event'   => $this->app->getName() . '.' . $event,
            'timestamp' => microtime(true),
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

        if ($this->get(Container::class)->hasInstance(Mapper::class)) {
            $serviceName = $this->app->getName();
            $this->get(Pool::class)->get($serviceName);
        }

        foreach ($this->get(Pool::class)->getMappers() as $mapper) {
            if ($mapper->getPlugin(Spy::class)->hasChanges()) {
                $hasChanges = true;
            }
        }
        return $hasChanges;
    }

    public function fireChanges(string $producer)
    {
        $timestamp = microtime(true);

        if ($this->get(Container::class)->hasInstance(Mapper::class)) {
            $serviceName = $this->app->getName();
            $this->get(Pool::class)->get($serviceName);
        }

        $dispatcher = $this->app->get(Dispatcher::class);
        $changed = false;

        foreach ($this->get(Pool::class)->getMappers() as $mapper) {
            $spy = $mapper->getPlugin(Spy::class);
            if ($spy->hasChanges()) {
                // reduce changes list
                $changes = $spy->getChanges();
                foreach ($changes as $action => $collection) {
                    foreach (['job_context', 'job_queue', 'job_result'] as $space) {
                        if (array_key_exists($space, $collection)) {
                            unset($collection[$space]);
                        }
                    }
                    if (count($collection)) {
                        $changes->$action = $collection;
                    } else {
                        unset($changes->$action);
                    }
                }

                // fire events
                if (count(get_object_vars($changes))) {
                    $changed = true;
                    foreach ($changes as $action => $set) {
                        foreach ($set as $space => $rows) {
                            foreach ($rows as $i => $row) {
                                $row = $this->app->get(Converter::class)->toArray($row);
                                if (array_key_exists('app', $row)) {
                                    unset($row['app']);
                                }

                                $this->send('event.fire', [
                                    'producer' => $producer,
                                    'event' => "$mapper->serviceName.$space.$action",
                                    'context' => $row,
                                    'timestamp' => $timestamp,
                                ]);
                            }
                        }
                    }
                }

                $spy->reset();
            }
        }
        return $changed;
    }
}
