<?php

namespace Basis;

use Exception;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

class Queue extends Client
{
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
            return new Task($this, $tube, $tasks[0][0], $tasks[0][2]);
        }
    }

    public function put($tube, $task, $options = [])
    {
        return $this->evaluate("require('queue').tube.$tube:put(...)", [$task, $options]);
    }

    public function has($tube)
    {
        $query = "return #box.space._queue.index.tube:select('$tube') > 0";
        return $this->evaluate($query)->getData()[0];
    }
}