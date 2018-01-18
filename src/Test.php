<?php

namespace Basis;

use Basis\Converter;
use Basis\Runner;
use Exception;
use PHPUnit\Framework\TestCase;

abstract class Test extends TestCase
{
    use Toolkit;

    public $params = [];

    public function __construct()
    {
        parent::__construct();

        $this->app = new class(getcwd(), $this) extends Application {
            public function __construct(string $root, Test $testInstance)
            {
                parent::__construct($root);
                $this->testInstance = $testInstance;
            }
            public function dispatch(string $job, array $params = [], string $service = null)
            {
                if (array_key_exists($job, $this->testInstance->mocks)) {
                    $mocks = $this->testInstance->mocks[$job];
                    $valid = null;
                    foreach ($mocks as $mock) {
                        if ($mock->params == $params || (!$mock->params && !$valid)) {
                            $valid = $mock;
                        }
                    }
                    if ($valid) {
                        $result = $valid->result;
                        if (is_callable($result)) {
                            $result = $result($params);
                        }
                        $valid->calls++;
                        return $this->get(Converter::class)->toObject($result);
                    }
                }
                if (!$this->get(Runner::class)->hasJob($job)) {
                    throw new Exception("Remote calls are disabled for tests");
                }
                $converter = $this->get(Converter::class);
                $global = $this->testInstance->params ?: [];
                if (!is_array($global)) {
                    $global = get_object_vars($converter->toObject($this->testInstance->params));
                }
                return parent::dispatch($job, array_merge($params, $global), $service);
            }
        };
    }

    public function setup()
    {
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown()
    {
        $this->dispatch('tarantool.clear');
    }

    public $mocks = [];
    public function mock(string $job, array $params = [])
    {
        if (!array_key_exists($job, $this->mocks)) {
            $this->mocks[$job] = [];
        }

        $mock = new class {

            public $params;
            public $result;
            public $calls = 0;

            public function withParams($params)
            {
                $this->params = $params;
                return $this;
            }

            public function handler($result)
            {
                $this->result = $result;
                return $this;
            }

            public function willDo($result)
            {
                return $this->handler($result);
            }

            public function willReturn($result)
            {
                return $this->handler($result);
            }
        };

        if (count($params)) {
            $mock->params = $params;
        }

        $this->mocks[$job][] = $mock;

        return $mock;
    }
}
