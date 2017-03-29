<?php

namespace Example\Jobs\Hello;

use Basis\Service;

/**
 * Example job for greeting
 */
class Session
{
    public $session;

    function run()
    {
        $message = "hello $this->session";
        return compact('message');
    }
}