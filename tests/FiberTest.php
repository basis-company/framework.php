<?php

use Basis\Fiber;

class FiberTest extends TestSuite
{
    function testProvider()
    {
        $this->assertSame(
            $this->app->get(Fiber::class),
            $this->app->get(Fiber::class)
        );
    }

    function testContextChange()
    {
        $fiber = $this->app->get(Fiber::class);

        $result = (object) [
            'data' => []
        ];

        $fiber->attach(function() use ($fiber, $result) {
            $result->data[] = 1;

            $fiber->attach(function() use ($result) {
                $result->data[] = 5;
                yield;
                $result->data[] = 7;
            });

            $fiber->run();
            
            yield;
            $result->data[] = 3;
        });

        $fiber->attach(function() use ($result) {
            $result->data[] = 2;
            yield;
            $result->data[] = 4;
            yield;
            $result->data[] = 6;
        });

        $fiber->run();

        $this->assertSame($result->data, [1,2,3,4,5,6,7]);

    }

    function testContextSwitch()
    {
        $fiber = $this->app->get(Fiber::class);

        $result = (object) [
            'data' => []
        ];

        $fiber->attach(function() use ($result) {
            $result->data[] = 1;
            yield;
            $result->data[] = 3;
        });

        $fiber->attach(function() use ($result) {
            $result->data[] = 2;
            yield;
            $result->data[] = 4;
        });

        $fiber->run();

        $this->assertSame($result->data, [1,2,3,4]);
    }
}