<?php

namespace Jobs\Hello;

/**
 * Example job for greeting
 */
class World
{
    public $name = 'world';

    public function run()
    {
        $message = "hello $this->name!";
        return compact('message');
    }
}
