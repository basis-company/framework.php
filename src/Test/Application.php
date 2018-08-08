<?php

namespace Basis\Test;

use Basis\Application as BaseApplication;
use Basis\Cache;
use Basis\Context;
use Basis\Converter;
use Basis\Runner;
use Basis\Test;
use Exception;

class Application extends BaseApplication
{
    private $test;

    public function __construct(Test $test)
    {
        parent::__construct(getcwd());

        $this->test = $test;
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if (array_key_exists('context', $this->test->params)) {
            $this->get(Context::class)->apply($this->test->params['context']);
        }
        if (array_key_exists($job, $this->test->mockInstances)) {
            $mocks = $this->test->mockInstances[$job];
            $valid = null;
            foreach ($mocks as $mock) {
                if ($mock->params == $params || (!$mock->params && !$valid)) {
                    $valid = $mock;
                }
            }
            if ($valid) {
                return $this->get(Cache::class)
                    ->wrap([$job, $params, $service], function () use ($valid, $params) {
                        $result = $valid->result;
                        if (is_callable($result)) {
                            $result = $result($params);
                        }
                        $valid->calls++;
                        return $this->get(Converter::class)->toObject($result);
                    });
            }
        }
        if ($this->test->disableRemote) {
            if (!$this->get(Runner::class)->hasJob($job)) {
                throw new Exception("Remote calls ($job) are disabled for tests");
            }
        }

        $converter = $this->get(Converter::class);

        $global = $this->test->params ?: [];
        $global = $converter->toObject($global);
        if (is_object($global)) {
            $global = get_object_vars($global);
        }

        return parent::dispatch($job, array_merge($params, $global), $service);
    }
}
