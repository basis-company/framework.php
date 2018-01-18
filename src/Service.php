<?php

namespace Basis;

use Exception;
use Tarantool\Mapper\Pool;

class Service
{
    private $app;
    private $name;

    public function __construct($name, Application $app)
    {
        $this->app = $app;
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    private $services;

    public function listServices() : array
    {
        return $this->services ?: $this->services = $this->app->dispatch('web.services')->names;
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

    public function eventExists(string $event) : bool
    {
        $types = $this->app->get(Pool::class)->get('event')->find('type');

        foreach ($types as $type) {
            if ($this->eventMatch($event, $type->nick)) {
                return true;
            }
        }

        return false;
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
}
