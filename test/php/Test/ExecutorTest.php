<?php

namespace Test;

use Basis\Context;
use Basis\Executor;
use Basis\Test;

class ExecutorTest extends Test
{
    public function testKeepContextsWhenJobExists()
    {
        $executor = $this->get(Executor::class);
        $sent = $executor->send('actor', []);
        $this->assertCount(1, $this->find('job_context'));
        $this->assertCount(1, $this->find('job_queue'));
        [ $context ] = $this->find('job_context');
        $context->activity = 1;
        $context->save();

        $executor->cleanup();

        $this->getRepository('job_context')->forget($context->id);
        $this->assertCount(1, $this->find('job_context'));
        [ $context ] = $this->find('job_context');
        $this->assertNotEquals($context->activity, 1);

        $this->getRepository('job_context')->flushCache();
        $this->getRepository('job_queue')->flushCache();
        $this->assertCount(1, $this->find('job_context'));
        $this->assertCount(1, $this->find('job_queue'));

        $this->getMapper()->getClient()->call('box.space.job_queue:truncate');
        $context->activity = 1;
        $context->save();
        $executor->cleanup();
        $this->assertCount(0, $this->find('job_context'));
    }

    public function testHashParams()
    {
        $note = $this->create('note', []);
        $result = $this->get(Executor::class)->send('actor', ['note' => $note->id, 'job_queue_hash' => 'bazyaba']);
        $request = $this->findOrFail('job_queue');
        $this->assertSame($request->hash, 'bazyaba');
    }

    public function testDispatcherProcessingTrigger()
    {
        $note = $this->create('note', []);
        // REALLY NEED PREFIX FOR ANY LOCAL JOB?
        $result = $this->get(Executor::class)->dispatch('actor', ['note' => $note->id]);
        $this->assertNotNull($result);
    }
    public function testContext()
    {
        $note = $this->create('note', []);
        $this->send('actor', ['note' => $note->id]);
        $this->dispatch('nats.consume', [ 'stream' => $this->app->getName(), 'limit' => 1 ]);
        $this->assertSame($note->message, '');

        $this->actAs(1);
        $this->send('actor', ['note' => $note->id]);

        $this->actAs(2);
        $this->dispatch('nats.consume', [ 'stream' => $this->app->getName(), 'limit' => 1 ]);

        $this->assertSame($note->message, '1');
        $this->assertSame($this->get(Context::class)->getPerson(), 2);
    }

    public function testBasics()
    {
        $note = $this->create('note', ['message' => 5]);
        $this->actAs(1);
        $this->send('increment', ['note' => $note->id]);
        $this->assertEquals($note->message, 5);

        $this->assertCount(0, $this->find('job_queue'));

        $this->dispatch('nats.consume', [
            'stream' => $this->app->getName(),
            'batch' => 1,
            'limit' => 1,
        ]);
        $this->assertEquals($note->message, 6);

        // register changes
        $this->mock('event.changes');
        $this->get(Executor::class)->process();

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

        $result = $this->get(Executor::class)->getResult($request->hash, $request->service);
        $this->assertEquals($result->note->message, 7);
    }
}
