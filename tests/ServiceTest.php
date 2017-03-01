<?php

use Basis\Queue;
use Basis\Service;
use Ramsey\Uuid\Uuid;
use Tarantool\Client\Client;

class ServiceTest extends TestSuite
{
    function setup()
    {
        parent::setup();
        $queue = $this->app->get(Queue::class);
        $queue->init('router');
        $queue->truncate('router');

        $this->app->get(Service::class);
        $task = $queue->take('router');
        $this->assertNotNull($task);
        $this->assertSame($task['job'], 'router.register');
        $task->ack();
    }

    function testInstance()
    {
        $app = $this->app;

        $service = $app->get(Service::class);
        $this->assertSame($service, $app->get(Service::class));
    }

    function testSending()
    {
        $queue = $this->app->get(Queue::class);
        $queue->init('router');

        $service = $this->app->get(Service::class);
        $service->send('event.trigger', [
            'name' => 'person.update',
            'id' => 1,
        ]);

        $task = $queue->take('router');
        $this->assertNotNull($task);
        $this->assertNotNull($task['uuid']);
        $this->assertSame($task['job'], 'router.process');
        $this->assertSame($task['data']['job'], 'event.trigger');
        $this->assertSame($task['data']['data']['name'], 'person.update');
        $this->assertSame($task['data']['data']['id'], 1);
        
    }

    function testProcessing()
    {
        $service = $this->app->get(Service::class);

        $queue = $this->app->get(Queue::class);
        $queue->init('some_service');
        $queue->truncate('some_service');
        $queue->truncate($service->getTube());

        // call with params
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.world',
            'data' => [
                'name' => 'nekufa',
            ]
        ]);

        // call
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.world',
        ]);

        // no job
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.nobody',
        ]);

        // no response
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'job' => 'hello.world',
        ]);

        $service->process();
        $service->process();
        $service->process();
        $service->process();
        $service->process();

        $task = $queue->take('some_service');
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello nekufa!');
        $task->ack();

        
        $task = $queue->take('some_service');
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello world!');
        $task->ack();

        $task = $queue->take('some_service');
        $this->assertNotNull($task);
        $this->assertTrue($task['error']);
        $this->assertSame($task['data'], 'No job hello.nobody');
        $task->ack();

        $task = $queue->take('some_service');
        $this->assertNull($task);
    }
}
