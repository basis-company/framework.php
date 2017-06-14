<?php

namespace Job;

/**
 * Example job for greeting
 */
class Hello
{
    public $name = 'world';

    public function run()
    {
        $message = "hello $this->name!";
        return compact('message');
    }
}
