<?php

namespace Listener;

class CreateLogin
{
    public static $events = [
        'person.created',
    ];

    public $event;
    public $context;

    public function run()
    {
    }
}
