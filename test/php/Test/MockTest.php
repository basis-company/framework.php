<?php

namespace Test;

use Basis\Test;

class MockTest extends Test
{
    public $mocks = [
        ['say.hello', ['nick' => 'nekufa'], 'hello'],
        ['service.call', ['name' => 'nekufa'], ['value' => 'nekufa!']],
        ['service.call', ['name' => 'bazyaba'], ['value' => 'bazyaba!']],
        ['service.call', [], ['value' => 'anonymous!']],
    ];

    public function hello($params)
    {
        return ['text' => 'Hello, '.$params->nick];
    }

    public function testContext()
    {
        $this->assertSame('Hello, nekufa', $this->dispatch('say.hello', ['nick' => 'nekufa'])->text);
    }

    public function testInstanceParams()
    {
        $this->params = ['name' => 'tester'];
        $this->assertSame('hello tester!', $this->dispatch('test.hello', [])->message);
    }

    public function testMocking()
    {
        foreach ($this->mocks as [$job, $params, $value]) {
            if (is_string($value) || is_callable($value)) {
                continue;
            }
            $result = $this->dispatch($job, $params);
            $this->assertSame(get_object_vars($result), $value);
        }


        $this->expectExceptionMessage("Remote calls (remote.call) are disabled for tests");
        $this->dispatch('remote.call');
    }
}
