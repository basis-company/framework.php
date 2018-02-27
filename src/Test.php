<?php

namespace Basis;

use Basis\Converter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tarantool\Mapper\Pool;

abstract class Test extends TestCase
{
    use Toolkit;

    public $params = [];
    public $disableRemote = true;

    public $mocks = [];
    public $data = [];

    public function __construct()
    {
        parent::__construct();

        $this->app = new Test\Application($this);

        foreach ($this->mocks as [$method, $params, $result]) {
            $this->mock($method, $params)->willReturn($result);
        }

        $serviceData = [];
        foreach ($this->data as $space => $data) {
            [$service, $space] = explode('.', $space);
            if (!array_key_exists($service, $serviceData)) {
                $serviceData[$service] = [];
            }
            $serviceData[$service][$space] = $data;
        }

        $pool = $this->app->get(Pool::class);
        $property = new ReflectionProperty(Pool::class, 'resolvers');
        $property->setAccessible(true);
        $property->setValue($pool, []);

        $pool->registerResolver(function($service) use ($serviceData) {
            if (array_key_exists($service, $serviceData)) {
                $mapper = new Test\Mapper($serviceData[$service]);
                $mapper->serviceName = $service;
                return $mapper;
            }
        });
    }

    public function setup()
    {
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown()
    {
        $this->dispatch('tarantool.clear');
    }

    public $mockInstances = [];

    public function mock(string $job, array $params = [])
    {
        if (!array_key_exists($job, $this->mockInstances)) {
            $this->mockInstances[$job] = [];
        }

        $mock = new Test\Mock($this->get(Converter::class), $this, $params);

        $this->mockInstances[$job][] = $mock;

        return $mock;
    }
}
