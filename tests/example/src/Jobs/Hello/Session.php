<?php

namespace Example\Jobs\Hello;

use Basis\Service;

/**
 * Example job for greeting
 */
class Session
{
    public $session;

    function run(Service $service)
    {
        $message = "hello ".($this->session ?: $service->getSession());
        return compact('message');
    }
}