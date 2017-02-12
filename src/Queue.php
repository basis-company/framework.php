<?php

namespace Basis;

use Exception;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

class Queue extends Client
{
    public function __construct(Config $config)
    {
        $connection = new StreamConnection('tcp://'.$config['queue.host'].':'.$config['queue.port'], [
            'socket_timeout' => $config['queue.socket_timeout'] ?: 5,
            'connect_timeout' => $config['queue.connect_timeout'] ?: 5,
        ]);
        parent::__construct($connection, new PurePacker());
    }

    public function init($tube, $type = 'fifottl')
    {
        return $this->evaluate("
            box.once('$tube-tube', function()
                local queue = require('queue')
                queue.create_tube('$tube', '$type')
            end)
        ");
    }

    public function truncate($tube)
    {
        $this->evaluate("require('queue').tube.$tube:truncate()");
    }

    public function take($tube, $timeout = 1)
    {
        $tasks = $this->evaluate("return require('queue').tube.$tube:take($timeout)")->getData();
        if(count($tasks)) {
            return $tasks[0];
        }
    }

    public function ack($tube, $task)
    {
        return $this->evaluate("require('queue').tube.$tube:ack($task)");
    }

    public function bury($tube, $task)
    {
        return $this->evaluate("require('queue').tube.$tube:bury($task)");
    }

    public function put($tube, $task, $options = [])
    {
        return $this->evaluate("require('queue').tube.$tube:put(...)", [$task, $options]);
    }

    public function release($tube, $id, $options = [])
    {
        return $this->evaluate("require('queue').tube.$tube:release($id, ...)", [$options]);
    }
}