<?php

namespace Basis;

use PHPUnit\Framework\TestCase;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Entity;

abstract class Test extends TestCase
{
    public function setup()
    {
        $root = getcwd();

        $service = getenv('SERVICE_NAME') ?: basename($root);
        $host = getenv('TARANTOOL_SERVICE_HOST') ?: '127.0.0.1';
        $port = getenv('TARANTOOL_SERVICE_PORT') ?: '3302';

        $this->app = new Application($root);
        $this->get(Config::class)['service'] = $service;
        $this->get(Config::class)['tarantool'] = "tcp://$host:$port";
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown()
    {
        $this->dispatch('tarantool.clear');
    }

    public function dispatch(string $job, array $params = [])
    {
        return $this->app->dispatch($job, $params);
    }

    public function get(string $class)
    {
        return $this->app->get($class);
    }

    public function getMapper() : Mapper
    {
        return $this->get(Mapper::class);
    }

    public function find(string $space, array $params = []) : array
    {
        return $this->getMapper()->find($space, $params);
    }

    public function findOne(string $space, array $params = []) : Entity
    {
        return $this->getMapper()->findOne($space, $params);
    }

    public function findOrFail(string $space, array $params = []) : Entity
    {
        return $this->getMapper()->findOrFail($space, $params);
    }

    public function findOrCreate(string $space, array $params = []) : Entity
    {
        return $this->getMapper()->findOrCreate($space, $params);
    }
}
