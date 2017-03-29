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
        ], [
            'session' => 'qwerty'
        ]);

        $task = $queue->take('router');
        $this->assertNotNull($task);
        $this->assertNotNull($task['uuid']);
        $this->assertSame($task['job'], 'router.process');
        $this->assertSame($task['data']['job'], 'event.trigger');
        $this->assertSame($task['data']['data']['name'], 'person.update');
        $this->assertSame($task['data']['data']['id'], 1);
        $this->assertSame($task['session'], 'qwerty');
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
            'session' => 'params',
            'data' => [
                'name' => 'nekufa',
            ]
        ]);

        // call
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.world',
            'session' => 'call',
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

        // session test
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.session',
            'session' => 'identifier'
        ]);

        // session keep
        $queue->put($service->getTube(), [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => 'some_service',
            'job' => 'hello.session',
            'data' => [
                'session' => 'original',
            ],
            'session' => 'identifier'
        ]);

        $tasks = $queue->getSpace($service->getTube())->select()->getData();

        $this->assertCount(6, $tasks);

        foreach($tasks as $task) {
            $service->process(0);
        }

        $task = $queue->take('some_service', 0);
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello nekufa! [params]');
        $task->ack();
        
        $task = $queue->take('some_service', 0);
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello world! [call]');
        $task->ack();

        $task = $queue->take('some_service', 0);
        $this->assertNotNull($task);
        $this->assertTrue($task['error']);
        $this->assertSame($task['data'], 'No job hello.nobody');
        $task->ack();

        $task = $queue->take('some_service', 0);
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello identifier');
        $task->ack();

        $task = $queue->take('some_service', 0);
        $this->assertNotNull($task);
        $this->assertSame($task['data']['message'], 'hello original');
        $task->ack();

        $task = $queue->take('some_service', 0);
        $this->assertNull($task);

    }
}
