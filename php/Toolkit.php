<?php

namespace Basis;

use Basis\Metric\Registry;
use OpenTelemetry\Tracing\Tracer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;
use Tarantool\Mapper\Repository;
use Tarantool\Queue\Queue;
use Throwable;

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

    public function deprecated($message = null)
    {
        $parent = debug_backtrace()[0];
        $log = [
            'msg' => $message,
            'file' => str_replace('/app/php/', '', $parent['file']),
            'line' => $parent['line'],
        ];
        if (!$message) {
            unset($log['msg']);
        }
        $this->warning('deprecated', $log);
    }

    public function exception(Throwable $e, string $level = LogLevel::ERROR)
    {
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        return $this->log($level, $e->getMessage(), $context);
    }

    public function log($level, $message, array $context = [])
    {
        if (is_array($message)) {
            $context = $message;
            $message = 'no message';
        }

        return $this->get(LoggerInterface::class)
            ->log($level, $message, $context);
    }

    public function info($message, $context = [])
    {
        return $this->log(LogLevel::INFO, ...func_get_args());
    }

    public function warning($message, $context = [])
    {
        return $this->log(LogLevel::WARNING, ...func_get_args());
    }

    public function error($message, $context = [])
    {
        return $this->log(LogLevel::ERROR, ...func_get_args());
    }

    public function send(string $job, array $params = [], string $service = null)
    {
        return $this->get(Executor::class)->send($job, $params, $service);
    }

    public function dispatch(string $job, array $params = [], string $service = null): object
    {
        return $this->get(Dispatcher::class)->dispatch($job, $params, $service);
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

    public function getSpaceName(int $entity): string
    {
        return $this->dispatch('resolve.space', compact('entity'))->name;
    }

    public function getEntityId(string $space): int
    {
        return $this->dispatch('resolve.entity', compact('space'))->id;
    }

    public function getAttributes(string $space, int $id, array $defaults = []): array
    {
        $result = $defaults;
        $attributeValues = $this->find('entity.attribute_value', [
            'entity' => $this->getEntityId($space),
            'entityId' => $id,
        ]);
        foreach ($attributeValues as $attributeValue) {
            $attribute = $this->findOrFail('entity.attribute', $attributeValue->attribute);
            $result[$attribute->nick] = $attributeValue->value;
        }
        return $result;
    }

    public function setAttributes(string $space, int $id, array $values)
    {
        foreach ($values as $k => $v) {
            $this->setAttribute($space, $id, $k, $v);
        }
    }

    public function setAttribute(string $space, int $id, string $key, string $value)
    {
        $attribute = $this->findOrCreate('entity.attribute', [
            'space' => $space,
            'nick' => $key,
        ], [
            'space' => $space,
            'nick' => $key,
            'name' => $key,
        ]);
        $attributeValue = $this->findOrCreate('entity.attribute_value', [
            'attribute' => $attribute->id,
            'entityId' => $id,
        ], [
            'attribute' => $attribute->id,
            'entity' => $this->getEntityId($space),
            'entityId' => $id,
            'value' => $value,
        ]);
        $attributeValue->value = $value;
        $attributeValue->save();
    }
}
