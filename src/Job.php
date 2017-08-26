<?php

namespace Basis;

use Tarantool\Mapper\{Mapper, Entity};

abstract class Job
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create(string $space, array $data) : Entity
    {
        return $this->get(Mapper::class)->create($space, $data);
    }

    public function dispatch(string $job, array $params = [])
    {
        return $this->app->dispatch($job, $params);
    }

    public function fire(string $event, array $context)
    {
        return $this->get(Event::class)->fire($event, $context);
    }

    public function get(string $class)
    {
        return $this->app->get($class);
    }

    public function find(string $space, array $params = []) : array
    {
        return $this->get(Mapper::class)->find($space, $params);
    }

    public function findOrCreate(string $space, array $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrCreate($space, $params);
    }

    public function findOne(string $space, array $params = []) : ?Entity
    {
        return $this->get(Mapper::class)->findOne($space, $params);
    }

    public function findOrFail(string $space, array $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrFail($space, $params);
    }

    public function remove(string $space, array $params = [])
    {
        return $this->get(Mapper::class)->remove($space, $params);
    }

    public function __debugInfo()
    {
        $info = get_object_vars($this);
        unset($info['app']);
        return $info;
    }
}
