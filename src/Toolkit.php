<?php

namespace Basis;

use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Queue\Queue;

trait Toolkit
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function create(string $space, array $data)
    {
        return $this->getRepository($space)->create($data)->save();
    }

    protected function dispatch(string $job, array $params = [], string $service = null)
    {
        return $this->app->dispatch($job, $params, $service);
    }

    protected function find(string $space, $params = []) : array
    {
        return $this->getRepository($space)->find($params);
    }

    protected function findOne(string $space, $params = [])
    {
        return $this->getRepository($space)->findOne($params);
    }

    protected function findOrCreate(string $space, $params = [])
    {
        return $this->getRepository($space)->findOrCreate($params);
    }

    protected function findOrFail(string $space, $params = [])
    {
        return $this->getRepository($space)->findOrFail($params);
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

    protected function getRepository($space)
    {
        if (strpos($space, '.') !== false) {
            return $this->get(Pool::class)->getRepository($space);
        }
        return $this->get(Mapper::class)->getRepository($space);
    }

    public function getQueue($tube)
    {
        $alias = "queue.$tube";
        if (!$this->app->hasInstance($alias, true)) {
            if (strpos($tube, '.') !== false) {
                [$service, $tube] = explode('.', $tube);
                $client = $this->get(Pool::class)->get($service)->getClient();
            } else {
                $client = $this->getMapper()->getClient();
            }
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
        return $this->getRepository($space)->remove($params);
    }

    protected function select($fields, string $table, array $params)
    {
        return $this->get(Clickhouse::class)->select($fields, $table, $params);
    }

    protected function insert(string $table, array $data, array $headers)
    {
        return $this->get(Clickhouse::class)->insert($table, $data, $headers);
    }

    protected function upload(string $filename, $contents) : string
    {
        return $this->get(Storage::class)->upload($filename, $contents);
    }

    protected function download(string $hash)
    {
        return $this->get(Storage::class)->download($hash);
    }

    public function __debugInfo()
    {
        $info = get_object_vars($this);
        unset($info['app']);
        return $info;
    }
}
