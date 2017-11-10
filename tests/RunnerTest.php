<?php

namespace Test;

use Basis\Job\Job\Info;
use Basis\Runner;
use Job\Hello;

class RunnerTest extends TestSuite
{
    public function test()
    {
        $result = $this->app->dispatch('example.hello');
        $this->assertSame($result, ['message' => 'hello world!']);

        $result = $this->app->dispatch('example.hello', ['name' => 'nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $result = $this->app->dispatch('hello');
        $this->assertSame($result, ['message' => 'hello world!']);

        $result = $this->app->dispatch('hello', ['name' => 'nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $jobs = $this->app->get(Runner::class)->getMapping();
        $this->assertNotNull($jobs);
        $this->assertContains('example.hello', array_keys($jobs));
        $this->assertSame($jobs['example.hello'], Hello::class);

        $jobs = $this->app->dispatch('module.meta')['jobs'];
        $this->assertNotNull($jobs);
        $this->assertCount(1, $jobs);
        $this->assertContains('hello', $jobs);
    }

    public function testArgumentCasting()
    {
        $result = $this->app->dispatch('example.hello', ['nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $result = $this->app->dispatch('example.hello', ['dmitry', 'krokhin']);
        $this->assertSame($result, ['message' => 'hello dmitry krokhin!']);
    }
}
