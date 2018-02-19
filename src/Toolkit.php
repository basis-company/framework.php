<?php

namespace Basis;

use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;

trait Toolkit
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create(string $space, array $data) : Entity
    {
        return $this->get(Mapper::class)->create($space, $data);
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        return $this->app->dispatch($job, $params, $service);
    }

    public function find(string $space, $params = []) : array
    {
        return $this->get(Mapper::class)->find($space, $params);
    }

    public function findOne(string $space, $params = []) : ?Entity
    {
        return $this->get(Mapper::class)->findOne($space, $params);
    }

    public function findOrCreate(string $space, $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrCreate($space, $params);
    }

    public function findOrFail(string $space, $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrFail($space, $params);
    }

    public function fire(string $event, array $context)
    {
        return $this->get(Event::class)->fire($event, $context);
    }

    public function get(string $class)
    {
        return $this->app->get($class);
    }

    public function getMapper()
    {
        return $this->get(Mapper::class);
    }

    public function remove(string $space, array $params = [])
    {
        return $this->get(Mapper::class)->remove($space, $params);
    }

    public function select($fields, string $table, array $params)
    {
        return $this->get(Clickhouse::class)->select($fields, $table, $params);
    }

    public function insert(string $table, array $data, array $headers)
    {
        return $this->get(Clickhouse::class)->insert($table, $data, $headers);
    }

    public function __debugInfo()
    {
        $info = get_object_vars($this);
        unset($info['app']);
        return $info;
    }
}
