<?php

namespace Test;

use Basis\Context;
use Basis\Executor;
use Basis\Test;

class ExecutorTest extends Test
{
    public function testDispatcherProcessingTrigger()
    {
        $note = $this->create('note', []);
        $result = $this->get(Executor::class)->dispatch('actor', ['note' => $note->id]);
        $this->assertNotNull($result);
    }
    public function testContext()
    {
        $note = $this->create('note', []);
        $this->send('actor', ['note' => $note->id]);
        $this->get(Executor::class)->process();
        $this->assertSame($note->message, '');

        $this->actAs(1);
        $this->send('actor', ['note' => $note->id]);

        $this->actAs(2);
        $this->get(Executor::class)->process();

        $this->assertSame($note->message, '1');
        $this->assertSame($this->get(Context::class)->getPerson(), 2);
    }

    public function testBasics()
    {
        $note = $this->create('note', ['message' => 5]);
        $this->actAs(1);
        $this->send('increment', ['note' => $note->id]);
        $this->assertEquals($note->message, 5);

        $this->assertCount(1, $this->find('job_queue'));
        $request = $this->findOne('job_queue');

        $this->get(Executor::class)->process();

        $this->assertCount(0, $this->find('job_queue'));
        $this->assertCount(0, $this->find('job_result'));
        $this->assertEquals($note->message, 6);

        $request = $this->get(Executor::class)->initRequest([
            'job' => 'increment',
            'recipient' => 'test',
            'params' => [
                'note' => $note->id,
            ],
        ]);

        $this->assertEquals($request->recipient, 'test');
        $this->assertCount(1, $this->find('job_queue'));
        $this->assertCount(0, $this->find('job_result'));

        $this->get(Executor::class)->process();
        $this->assertCount(0, $this->find('job_queue'));
        $this->assertCount(1, $this->find('job_result'));

        $result = $this->get(Executor::class)->getResult($request->hash);
        $this->assertEquals($result->note->message, 7);
    }
}
