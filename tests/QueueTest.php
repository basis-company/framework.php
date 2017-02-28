<?php

use Basis\Queue;
use Tarantool\Client\Client;

class QueueTest extends TestSuite
{
    function testConnection()
    {
        $client = $this->app->get(Client::class);
        $this->assertSame($client, $this->app->get(Client::class));

        $result =$client->ping();
        $this->assertTrue(true);
    }

    function testQueue()
    {
        $queue = $this->app->get(Queue::class);
        $this->assertSame($queue, $this->app->get(Queue::class));

        $queue->init('sending_requests');
        $queue->truncate('sending_requests');

        $queue->put('sending_requests', ['name' => 'nekufa']);

        $task = $queue->take('sending_requests');
        $this->assertSame($task['name'], 'nekufa');

        $anotherTask = $queue->take('sending_requests', 0);
        $this->assertNull($anotherTask);

        $task->release();
        $anotherTask = $queue->take('sending_requests', 0);
        $this->assertNotNull($anotherTask);

        $anotherTask->ack();

        $anotherTask = $queue->take('sending_requests', 0);
        $this->assertNull($anotherTask);
    }
}