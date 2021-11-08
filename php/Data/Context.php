<?php

namespace Basis\Data;

use Basis\Dispatcher;
use Tarantool\Client\Client;

class Context
{
    public function __construct(private string $service, private string $name)
    {
    }

    public function getName()
    {
        return $this->name;
    }

    public function getService()
    {
        return $this->service;
    }
}
