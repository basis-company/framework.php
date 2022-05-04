<?php

namespace Test;

use Basis\Context;
use Basis\Executor;
use Basis\Nats\Client;
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

        $this->assertCount(1, $this->find('job_context'));
        [ $context ] = $this->find('job_context');
        $this->assertNotEquals($context->activity, 1);

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
        $result = $this->get(Executor::class)->send('test.actor', ['note' => $note->id, 'job_queue_hash' => 'bazyaba']);
        $request = $this->findOrFail('job_queue');
        $this->assertSame($request->hash, 'bazyaba');
    }

    public function testDispatcherProcessingTrigger()
    {
        $note = $this->create('note', []);
        // REALLY NEED PREFIX FOR ANY LOCAL JOB?
        $result = $this->get(Executor::class)->dispatch('test.actor', ['note' => $note->id]);
        $this->assertNotNull($result);
    }

    public function testContext()
    {
        putenv('BASIS_ENVIRONMENT=use-nats');
        $note = $this->create('note', []);
        $this->send('test.actor', ['note' => $note->id]);
        $info = $this->get(Client::class)->getApi()->getStream('test')->info();
        $this->assertSame(1, $info->getValue('state.messages'));

        $this->dispatch('nats.consume', [ 'subject' => $this->app->getName(), 'limit' => 1 ]);
        $note = $this->findOrFail('note', $note->id);
        $this->assertSame($note->message, '');

        $this->actAs(1);
        $this->send('test.actor', ['note' => $note->id]);

        $this->actAs(2);
        $this->assertSame($this->get(Context::class)->getPerson(), 2);
        $this->dispatch('nats.consume', [ 'subject' => $this->app->getName(), 'limit' => 1 ]);
        $this->assertSame($this->get(Context::class)->getPerson(), 2);

        $note = $this->findOrFail('note', $note->id);
        $this->assertSame($note->message, '1');
        $this->assertSame($this->get(Context::class)->getPerson(), 2);
        putenv('BASIS_ENVIRONMENT=testing');
    }

    public function testBasics()
    {
        putenv('BASIS_ENVIRONMENT=use-nats');
        $note = $this->create('note', ['message' => 5]);
        $this->actAs(1);
        $this->send('test.increment', ['note' => $note->id]);
        $this->assertEquals($note->message, 5);

        $this->assertCount(0, $this->find('job_queue'));

        $this->dispatch('nats.consume', [
            'subject' => $this->app->getName(),
            'batch' => 1,
            'limit' => 1,
        ]);

        $note = $this->findOrFail('note', $note->id);
        $this->assertEquals($note->message, 6);

        // register changes
        $this->mock('event.changes');
        $this->get(Executor::class)->process();

        $request = $this->get(Executor::class)->initRequest([
            'job' => 'test.increment',
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
        putenv('BASIS_ENVIRONMENT=testing');
    }
}
