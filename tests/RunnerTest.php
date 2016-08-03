<?php

class RunnerTest extends TestSuite
{
    function testRunner()
    {
        $result = $this->app->dispatch('hello.world');
        $this->assertSame($result, ['message' => 'hello world!']);

        $result = $this->app->dispatch('hello.world', ['name' => 'nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);
    }
}