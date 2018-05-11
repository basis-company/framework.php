<?php

namespace Basis;

use Exception;
use Tarantool\Mapper\Pool;

class Service
{
    private $app;
    private $cache;
    private $name;

    public function __construct($name, Application $app, Cache $cache)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    private $services;

    public function listServices() : array
    {
        return $this->services ?: $this->services = $this->app->dispatch('web.services')->services;
    }

    public function subscribe(string $event)
    {
        $this->app->dispatch('event.subscribe', [
            'event' => $event,
            'service' => $this->getName(),
        ]);
    }

    public function unsubscribe(string $event)
    {
        $this->app->dispatch('event.unsubscribe', [
            'event' => $event,
            'service' => $this->getName(),
        ]);
    }

    private $eventExistence = [];

    public function eventExists(string $event) : bool
    {
        if (array_key_exists($event, $this->eventExistence)) {
            return $this->eventExistence[$event];
        }
        $types = $this->app->get(Pool::class)->get('event')->find('type');

        foreach ($types as $type) {
            if ($this->eventMatch($event, $type->nick)) {
                return $this->eventExistence[$event] = true;
            }
        }

        return $this->eventExistence[$event] = false;
    }

    public function eventMatch($event, $spec)
    {
        if ($spec == $event) {
            return true;
        } else if (strpos($spec, '*') !== false) {
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

    public function getHost($name)
    {
        if (array_key_exists('BASIS_ENVIRONMENT', $_ENV)) {
            if ($_ENV['BASIS_ENVIRONMENT'] == 'dev') {
                return (object) [
                    'address' => $name,
                ];
            }
        }
        return $this->cache->wrap('service-host-for-' . $name, function() use ($name) {
            return (object) [
                'address' => gethostbyname($name),
                'expire' => time() + 60 * 30,
            ];
        });
    }
}
