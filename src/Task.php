<?php

namespace Basis;

use ArrayAccess;

class Task implements ArrayAccess
{
    private $queue;
    private $tube;
    private $id;
    private $data;
    private $taken;

    function __construct(Queue $queue, $tube, $id, $data)
    {
        $this->queue = $queue;
        $this->tube = $tube;
        $this->id = $id;
        $this->data = $data;
        $this->taken = true;
    }

    private function apply($method, $options = null)
    {
        if(!$this->taken) {
            throw new Exception("Task was not taken in this session");
        }

        $this->taken = false;

        $callback = "require('queue').tube.{$this->tube}:{$method}";

        if(is_array($options)) {
            return $this->queue->evaluate("$callback({$this->id}, ...)", [$options]);
        }
        return $this->queue->evaluate("$callback({$this->id})");
    }

    public function ack()
    {
        return $this->apply('ack');
    }

    public function bury($options = [])
    {
        return $this->apply('bury', $options);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getTube()
    {
        return $this->tube;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        return $this->data[$offset] = $value;
    }
    
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function release($options = [])
    {
        return $this->apply('release', $options);
    }

    public function remove()
    {
        return $this->apply('delete');
    }
}