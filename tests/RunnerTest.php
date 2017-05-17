<?php

use Basis\Jobs\Job\Info;
use Basis\Runner;
use Jobs\Hello\World;

class RunnerTest extends TestSuite
{
    public function test()
    {
        $result = $this->app->dispatch('hello.world');
        $this->assertSame($result, ['message' => 'hello world!']);

        $result = $this->app->dispatch('hello.world', ['name' => 'nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $jobs = $this->app->get(Runner::class)->getMapping();
        $this->assertNotNull($jobs);
        $this->assertContains('hello.world', array_keys($jobs));
        $this->assertSame($jobs['hello.world'], World::class);

        $jobs = $this->app->dispatch('module.meta')['jobs'];
        $this->assertNotNull($jobs);
        $this->assertCount(1, $jobs);
        $this->assertContains('hello.world', $jobs);
    }

    public function testArgumentCasting()
    {
        $result = $this->app->dispatch('hello.world', ['nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $result = $this->app->dispatch('hello.world', ['dmitry', 'krokhin']);
        $this->assertSame($result, ['message' => 'hello dmitry krokhin!']);
    }
}
