<?php

namespace Example\Jobs\Hello;

use Basis\Service;

/**
 * Example job for greeting
 */
class World
{
    public $name = 'world';

    function run(Service $service)
    {
        $message = "hello $this->name!";
        if($service->getSession()) {
            $message .= ' [' . $service->getSession(). ']';
        }
        return compact('message');
    }
}