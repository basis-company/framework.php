<?php

namespace Basis\Test;

use Basis\Converter;
use Basis\Test;
use Exception;

class Mock
{
    private $converter;
    private $test;

    public $params;
    public $result;
    public $calls = 0;

    public function __construct(Converter $converter, Test $test)
    {
        $this->converter = $converter;
        $this->test = $test;
    }

    public function withParams($params)
    {
        $this->params = $params;
        return $this;
    }

    public function handler($result)
    {
        $this->result = $result;
        if (is_string($result)) {
            if (!method_exists($this->test, $result)) {
                throw new Exception("Invalid method ".get_class($this->test)."::$result");
            }
            $this->result = function($params) use ($result) {
                $params = $this->converter->toObject($params);
                return $this->test->$result($params);
            };
        }
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
}
