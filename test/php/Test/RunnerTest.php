<?php

namespace Test;

use Basis\Job\Job\Info;
use Basis\Test;
use Exception;
use Job\Hello;

class RunnerTest extends Test
{
    public function test()
    {
        $result = $this->dispatch('test.hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->dispatch('test.hello', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->dispatch('test.hello', ['name' => 'kek']);
        $this->assertSame($result->message, 'hello kek!');

        $result = $this->dispatch('test.hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->dispatch('test.helloSomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->dispatch('test.HelloSomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->dispatch('test.hellosomebody', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $result = $this->dispatch('hello');
        $this->assertSame($result->message, 'hello world!');

        $result = $this->dispatch('hello', ['name' => 'nekufa']);
        $this->assertSame($result->message, 'hello nekufa!');

        $jobs = $this->dispatch('module.meta')->jobs;
        $this->assertNotNull($jobs);
        $this->assertCount(5, $jobs);
        $this->assertContains('test.hello', $jobs);
    }

    public function testConfirmation()
    {
        $hash = null;

        try {
            $this->dispatch('test.hello', ['name' => 'bazyaba']);
            $this->assertNull('confirmation should be thrown');
        } catch (Exception $e) {
            $message = json_decode($e->getMessage());
            if (!is_object($message)) {
                throw $e;
            }
            $this->assertSame($message->type, 'confirm');
            $this->assertSame($message->message, 'bazyaba?');
            $hash = $message->hash;
        }

        $this->assertNotNull($hash);
        $result = $this->dispatch('test.hello', ['name' => 'bazyaba', '_confirmations' => [$hash]]);

        $this->assertSame($result->message, 'hello bazyaba!');
    }
}
