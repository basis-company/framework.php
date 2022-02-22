<?php

namespace Basis;

use Basis\Converter;
use Carbon\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;

abstract class Test extends TestCase
{
    use Toolkit;

    public $params = [];
    public $disableRemote = true;

    public $mocks = [];
    public $data = [];

    public function actAs($context)
    {
        if (is_numeric($context)) {
            $context = [
                'person' => $context,
            ];
        }
        return $this->get(Context::class)->reset($context);
    }

    public function __construct()
    {
        parent::__construct();

        $this->app = new Test\Application($this);
        $this->app->get(Cache::class)->clear();

        $this->logger = new Logger(get_called_class());
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
        $this->app->getContainer()->share(LoggerInterface::class, $this->logger);

        foreach ($this->mocks as [$method, $params, $result]) {
            $this->mock($method, $params)->willReturn($result);
        }
        $this->mock('event.subscription')->willReturn(['skip' => true]);

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

        $service = $this->app->getName();
        $pool->register($service, function () {
            return $this->app->get(Mapper::class);
        });

        $pool->registerResolver(function ($service) use ($serviceData) {
            if (array_key_exists($service, $serviceData)) {
                return new Test\Mapper($this, $service);
            }
        });

        foreach ($this->data ?: [] as $space => $rows) {
            $this->data[$space] = [];
            foreach ($rows as $row) {
                $pool->getRepository($space)->create($row)->save();
            }
        }
    }

    public function setUp(): void
    {
        Carbon::setTestNow(null);
        $this->dispatch('data.migrate');
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown(): void
    {
        $this->dispatch('data.clear');
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
