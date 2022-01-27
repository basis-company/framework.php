<?php

namespace Test;

use Basis\Cache;
use Basis\Test;
use Carbon\Carbon;

class DispatcherTest extends Test
{
    public $disableRemote = false;

    public function testMeta()
    {
        $meta = $this->dispatch('module.meta');
        $this->assertNotContains('hello', $meta->jobs);
        $this->assertContains('test.hello', $meta->jobs);
        $this->assertNotContains('module.bootstrap', $meta->jobs);
        // overrides should not present too
        $this->assertNotContains('module.contents', $meta->jobs);

        // meta not filtering framework jobs
        $meta = $this->dispatch('module.meta', ['filter' => false]);
        $this->assertNotContains('hello', $meta->jobs);
        $this->assertContains('test.hello', $meta->jobs);
        $this->assertContains('module.bootstrap', $meta->jobs);
        $this->assertContains('module.contents', $meta->jobs);
    }

    public function testCaching()
    {
        $this->mock('say.hello')->willReturn(function ($params) {
            return [
                'msg' => 'Hello, ' . $params['name'],
                'hash' => md5(microtime(1)),
                'expire' => Carbon::now()->addHour()->getTimestamp(),
            ];
        });

        $nekufa1 = $this->dispatch('say.hello', ['name' => 'nekufa']);
        $nekufa2 = $this->dispatch('say.hello', ['name' => 'nekufa']);
        $vasya1 = $this->dispatch('say.hello', ['name' => 'vasya']);
        $this->assertSame($nekufa1->hash, $nekufa2->hash);
        $this->assertNotSame($nekufa1->hash, $vasya1->hash);
    }

    public function testJobOverwrite()
    {
        $result = $this->dispatch('module.contents');
        $this->assertSame($result->message, 'ok');
    }
}
