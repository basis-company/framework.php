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
        $connection = new StreamConnection('tcp://'.$config['queue.host'].':'.$config['queue.port']);
        parent::__construct($connection, new PurePacker());
    }

    public function init($tube)
    {
        return $this->evaluate("
            box.once('$tube-tube', function()
                local queue = require('queue')
                queue.create_tube('$tube', 'fifottl')
            end)
        ");
    }

    public function truncate($tube)
    {
        $this->evaluate("require('queue').tube.$tube:truncate()");
    }

    public function take($tube, $timeout = 30)
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

    public function put($tube, $task)
    {
        return $this->evaluate("require('queue').tube.$tube:put(...)", [$task]);
    }
}