<?php

namespace Test;

use Basis\Job\Job\Info;
use Basis\Runner;
use Job\Hello;
use Basis\Test;

class RunnerTest extends Test
{
    public function testMultiple()
    {
        $result = $this->app->dispatch('test.hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->app->dispatch('test.hello', []);
        $this->assertSame($result->message, 'hello world!');

        $result = $this->app->dispatch('test.hello', [['name' => 'nekufa']]);
        $this->assertCount(1, $result);
        $this->assertSame($result[0]->message, 'hello nekufa!');

        $result = $this->app->dispatch('test.hello', [['name' => 'nekufa'], []]);
        $this->assertCount(2, $result);
        $this->assertSame($result[0]->message, 'hello nekufa!');
        $this->assertSame($result[1]->message, 'hello world!');

        $result = $this->app->dispatch('test.hello', [['name' => 'nekufa'], ['name' => 'vasya']]);
        $this->assertCount(2, $result);
        $this->assertSame($result[0]->message, 'hello nekufa!');
        $this->assertSame($result[1]->message, 'hello vasya!');
    }

    public function test()
    {
        $result = $this->app->dispatch('test.hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->app->dispatch('test.hello', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->app->dispatch('test.helloSomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->app->dispatch('test.HelloSomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->app->dispatch('test.hellosomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->app->dispatch('hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->app->dispatch('hello', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $jobs = $this->app->get(Runner::class)->getMapping();
        $this->assertNotNull($jobs);
        $this->assertContains('test.hello', array_keys($jobs));
        $this->assertSame($jobs['test.hello'], Hello::class);

        $jobs = $this->app->dispatch('module.meta')->jobs;
        $this->assertNotNull($jobs);
        $this->assertCount(2, $jobs);
        $this->assertContains('hello', $jobs);
    }

    public function testArgumentCasting()
    {
        $result = $this->app->dispatch('test.hello', ['nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->app->dispatch('test.hello', ['dmitry', 'krokhin']);
        $this->assertSame($result->message, 'hello dmitry krokhin!');
    }
}
