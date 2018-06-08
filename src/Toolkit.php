<?php

namespace Basis;

use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Queue\Queue;

trait Toolkit
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function create(string $space, array $data) : Entity
    {
        return $this->get(Mapper::class)->create($space, $data);
    }

    protected function dispatch(string $job, array $params = [], string $service = null)
    {
        return $this->app->dispatch($job, $params, $service);
    }

    protected function find(string $space, $params = []) : array
    {
        return $this->get(Mapper::class)->find($space, $params);
    }

    protected function findOne(string $space, $params = []) : ?Entity
    {
        return $this->get(Mapper::class)->findOne($space, $params);
    }

    protected function findOrCreate(string $space, $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrCreate($space, $params);
    }

    protected function findOrFail(string $space, $params = []) : Entity
    {
        return $this->get(Mapper::class)->findOrFail($space, $params);
    }

    protected function fire(string $event, array $context)
    {
        return $this->get(Event::class)->fire($event, $context);
    }

    protected function get(string $class)
    {
        return $this->app->get($class);
    }

    protected function getDate()
    {
        return call_user_func_array([$this->get(Converter::class), 'getDate'], func_get_args());
    }

    protected function getMapper()
    {
        return $this->get(Mapper::class);
    }

    protected function getQueue($tube)
    {
        $alias = "queue.$tube";
        if (!$this->app->hasShared($alias, true)) {
            $client = $this->getMapper()->getClient();
            $client->evaluate("
                if queue == nil then
                    queue = require('queue')
                end
            ");
            $this->app->share($alias, new Queue($client, $tube));
        }

        return $this->app->get($alias);
    }

    protected function remove(string $space, array $params = [])
    {
        return $this->get(Mapper::class)->remove($space, $params);
    }

    protected function select($fields, string $table, array $params)
    {
        return $this->get(Clickhouse::class)->select($fields, $table, $params);
    }

    protected function insert(string $table, array $data, array $headers)
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
