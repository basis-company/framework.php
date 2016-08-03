<?php

namespace Example\Jobs\Hello;

class World
{
    public $name = 'world';

    function run()
    {
        $message = "hello $this->name!";
        return compact('message');
    }
}