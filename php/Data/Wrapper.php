<?php

namespace Basis\Data;

use Tarantool\Client\Client;

class Wrapper
{
    private array $instances = [];

    private array $crud = [];
    private array $procedure = [];
    private array $queue = [];

    public function __construct(private string $service, private Client $client)
    {
    }

    public function get($class, string $name)
    {
        if (!array_key_exists($class, $this->instances)) {
            $this->instances[$class] = [];
        }

        if (!array_key_exists($name, $this->instances[$class])) {
            $this->instances[$class][$name] = new $class($this, $name);
        }

        return $this->instances[$class][$name];
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getCrud(string $space): Crud
    {
        return $this->get(Crud::class, $space);
    }

    public function getProcedure(string $name): Procedure
    {
        return $this->get(Procedure::class, $name);
    }

    public function getQueue(string $name): Queue
    {
        return $this->get(Queue::class, $name);
    }

    public function getService(): string
    {
        return $this->service;
    }
}
