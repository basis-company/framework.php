<?php

namespace Listener\Create;

class Login
{
    public static $events = [
        'person.created'
    ];

    public $event;
    public $context;

    public function run()
    {
    }
}
