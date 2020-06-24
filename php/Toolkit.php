<?php

namespace Basis;

use Basis\Clickhouse;
use OpenTelemetry\Tracing\Tracer;
use Psr\Container\ContainerInterface;
use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Repository;
use Tarantool\Queue\Queue;

trait Toolkit
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function create(string $space, array $data = []): Entity
    {
        return $this->getRepository($space)->create($data)->save();
    }

    public function call()
    {
        return $this->getContainer()->call(...func_get_args());
    }

    public function send(string $job, array $params = [], string $service = null)
    {
        return $this->get(Executor::class)->send($job, $params, $service);
    }

    public function dispatch(string $job, array $params = [], string $service = null): object
    {
        return $this->get(Cache::class)->wrap(func_get_args(), function () use ($job, $params, $service) {
            return $this->get(Dispatcher::class)->dispatch($job, $params, $service);
        });
    }

    public function get(string $class): object
    {
        return $this->getContainer()->get($class);
    }

    public function getContainer(): Container
    {
        return $this->app->getContainer();
    }

    public function find(string $space, $params = []): array
    {
        return $this->getRepository($space)->find($params);
    }

    public function findOne(string $space, $params = [])
    {
        return $this->getRepository($space)->findOne($params);
    }

    public function findOrCreate(string $space, $params = [], $data = []): Entity
    {
        return $this->getRepository($space)->findOrCreate($params, $data);
    }

    public function findOrFail(string $space, $params = []): Entity
    {
        return $this->getRepository($space)->findOrFail($params);
    }

    protected function remove(string $space, array $params = [])
    {
        return $this->getRepository($space)->remove($params);
    }

    public function getRepository(string $space): Repository
    {
        if (strpos($space, '.') === false) {
            return $this->get(Mapper::class)
                ->getRepository($space);
        }
        return $this->get(Pool::class)
            ->getRepository($space);
    }

    protected function select($fields, string $table, array $params)
    {
        return $this->get(Clickhouse::class)->select($fields, $table, $params);
    }

    protected function insert(string $table, array $data, array $headers)
    {
        return $this->get(Clickhouse::class)->insert($table, $data, $headers);
    }

    protected function getDate()
    {
        return $this->get(Converter::class)->getDate(...func_get_args());
    }

    public function getMapper(): Mapper
    {
        return $this->get(Mapper::class);
    }

    public function getQueue($tube): Queue
    {
        $container = $this->getContainer();
        $alias = "queue.$tube";

        if (!$container->has($alias, true)) {
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
            $container->share($alias, new Queue($client, $tube));
        }

        return $container->get($alias);
    }

    public function upload(string $filename, $contents): string
    {
        return $this->get(Storage::class)->upload($filename, $contents);
    }

    public function download(string $hash)
    {
        return $this->get(Storage::class)->download($hash);
    }

    public function span(?string $name = null, ?callable $callback = null)
    {
        $tracer = $this->get(Tracer::class);

        if ($name === null) {
            return $tracer->getActiveSpan();
        }

        $span = $tracer->createSpan($name);

        if ($callback === null) {
            return $span;
        }

        $container = $this->getContainer();
        $result = $container->call($callback, null, [ 'span' => $span ]);

        $span->end();

        return $result;
    }
}
