<?php

namespace Basis;

use PHPUnit\Framework\TestCase;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Entity;

abstract class Test extends TestCase
{
    public $params = [];

    public function setup()
    {
        $this->app = new Application(getcwd());
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown()
    {
        $this->dispatch('tarantool.clear');
    }

    public function dispatch(string $job, array $params = [])
    {
        return $this->app->dispatch($job, array_merge($params, $this->params));
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

    public function findOne(string $space, array $params = [])
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
