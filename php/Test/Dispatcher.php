<?php

namespace Basis\Test;

use Basis\Cache;
use Basis\Converter;
use Basis\Dispatcher as Basis;
use Basis\Test;
use Basis\Toolkit;
use Exception;

class Dispatcher extends Basis
{
    public function dispatch(string $job, $params = [], $service = null): object
    {
        $test = $this->get(Test::class);

        if (array_key_exists('context', $test->params)) {
            $this->get(Context::class)->apply($test->params['context']);
        }
        if (array_key_exists($job, $test->mockInstances)) {
            $mocks = $test->mockInstances[$job];
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

        if ($test->disableRemote) {
            if (!$this->isLocalJob($job)) {
                throw new Exception("Remote calls ($job) are disabled for tests");
            }
        }

        $converter = $this->get(Converter::class);

        $global = $test->params ?: [];
        $global = $converter->toObject($global);
        if (is_object($global)) {
            $global = get_object_vars($global);
        }

        return parent::dispatch($job, array_merge($params, $global), $service);
    }
}
