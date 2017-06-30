<?php

namespace Basis;

use Tarantool\Mapper\Mapper;

abstract class Job
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create($space, $data)
    {
        return $this->get(Mapper::class)->create($space, $data);
    }

    public function dispatch($job, $params = [])
    {
        return $this->app->dispatch($job, $params);
    }

    public function fire($event, $context)
    {
        return $this->get(Event::class)->fire($event, $context);
    }

    public function get($class)
    {
        return $this->app->get($class);
    }

    public function find($space, $params = [])
    {
        return $this->get(Mapper::class)->find($space, $params);
    }

    public function findOrCreate($space, $params = [])
    {
        return $this->get(Mapper::class)->findOrCreate($space, $params);
    }

    public function findOne($space, $params = [])
    {
        return $this->get(Mapper::class)->findOne($space, $params);
    }

    public function remove($space, $params = [])
    {
        return $this->get(Mapper::class)->remove($space, $params);
    }
}
