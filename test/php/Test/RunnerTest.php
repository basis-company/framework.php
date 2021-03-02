<?php

namespace Test;

use Basis\Job\Job\Info;
use Basis\Job\Module\Recover;
use Basis\Test;
use Exception;
use Job\Hello;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;

class RunnerTest extends Test
{
    public function testRecover()
    {
        $this->send('hello.world');

        $queue = $this->findOrFail('job_queue');
        $this->assertSame($queue->status, 'new');

        $queue->status = 'processing';
        $queue->save();

        $space = $this->getMapper()
            ->getClient()
            ->getSpace('job_queue');

        $criteria = Criteria::key([$queue->id]);
        [$tuple] = $space->select($criteria);

        $this->assertCount(8, $tuple);

        // since timestamp was set
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 1);
        $this->assertSame($recover->recovered, 0);

        // no changes
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 0);
        $this->assertSame($recover->recovered, 0);

        $criteria = Criteria::key([$queue->id]);
        [$tuple] = $space->select($criteria);

        $past = $tuple[8]['since'] - $this->get(Recover::class)->timeout + 1;
        $space->update([$queue->id], Operations::set(8, [ 'since' => $past ]));

        // still no changes
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 0);
        $this->assertSame($recover->recovered, 0);

        $past = $tuple[8]['since'] - $this->get(Recover::class)->timeout;
        $space->update([$queue->id], Operations::set(8, [ 'since' => $past ]));

        // recovered job
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 0);
        $this->assertSame($recover->recovered, 1);

        [$tuple] = $space->select($criteria);
        $this->assertCount(9, $tuple);
        $this->getRepository('job_queue')->sync($queue->id);
        $this->assertSame($queue->status, 'new');

        // no more changes
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 0);
        $this->assertSame($recover->recovered, 0);

        $criteria = Criteria::key([$queue->id]);
        [$tuple] = $space->select($criteria);

        $this->assertNull($tuple[8]);

        $queue->status = 'processing';
        $queue->save();        

        // add timestamp
        $recover = $this->dispatch('module.recover');
        $this->assertSame($recover->new, 1);
        $this->assertSame($recover->recovered, 0);

        $criteria = Criteria::key([$queue->id]);
        [$tuple] = $space->select($criteria);

        $this->assertNotNull($tuple[8]);

    }


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
        $this->assertCount(6, $jobs);
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
