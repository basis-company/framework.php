<?php

use Tarantool\Client\Client;
use Basis\Queue;

class TarantoolTest extends TestSuite
{
    function testConnection()
    {
        $client = $this->app->get(Client::class);
        $client->ping();
        $this->assertTrue(true);
    }

    function testQueue()
    {
        $queue = $this->app->get(Queue::class);
        $queue->init('testing');
        $queue->truncate('testing');

        $data = [
            'name' => 'testing',
            'timestamp' => time()
        ];


        $queue->put('testing', $data);
        $task = $queue->take('testing');

        $this->assertNotNull($task);
        $this->assertEquals($task[2], $data);

        $queue->release('testing', $task[0], [
            'delay' => 2,
        ]);

        $task = $queue->take('testing');
        $this->assertNull($task);

        sleep(1);

        $task = $queue->take('testing');
        $this->assertNotNull($task);
        $queue->ack('testing', $task[0]);

        $task = $queue->take('testing');
        $this->assertNull($task);
    }
}