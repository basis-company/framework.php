<?php

namespace Listener;

class CreateLogin
{
    public static $events = [
        'person.created',
        'person.person.created',
    ];

    public $event;

    public $space;
    public $action;
    public $context;

    public function run()
    {
        $name = $this->context->name;
        return [
            'msg' => "$this->space was $this->action with name $name",
        ];
    }
}
