<?php

use Basis\Jobs\Job\Info;
use Basis\Runner;
use Example\Jobs\Hello\World;

class RunnerTest extends TestSuite
{
    function test()
    {
        $result = $this->app->dispatch('hello.world');
        $this->assertSame($result, ['message' => 'hello world!']);

        $result = $this->app->dispatch('hello.world', ['name' => 'nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $jobs = $this->app->get(Runner::class)->listJobs();
        $this->assertNotNull($jobs);
        $this->assertContains('job.info', array_keys($jobs));
        $this->assertContains('hello.world', array_keys($jobs));

        $this->assertSame($jobs['job.info'], Info::class);
        $this->assertSame($jobs['hello.world'], World::class);

        $jobs = $this->app->dispatch('service.getJobs')['jobs'];
        $this->assertNotNull($jobs);
        $this->assertCount(1, $jobs);
        $this->assertContains('hello.world', $jobs);

        $result = $this->app->dispatch('job.info');
        $hash = [];
        foreach($result['info'] as $row) {
            $hash[$row['nick']] = $row;
        }

        $this->assertSame($hash['job.info']['comment'], 'Get Jobs information');
        $this->assertSame($hash['hello.world']['comment'], 'Example job for greeting');
    }

    function testArgumentCasting()
    {
        $result = $this->app->dispatch('hello.world', ['nekufa']);
        $this->assertSame($result, ['message' => 'hello nekufa!']);

        $result = $this->app->dispatch('hello.world', ['dmitry', 'krokhin']);
        $this->assertSame($result, ['message' => 'hello dmitry krokhin!']);
    }
}