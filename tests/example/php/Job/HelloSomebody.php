<?php

namespace Job;

/**
 * Example job for greeting
 */
class HelloSomebody
{
    public $name = 'world';

    public function run()
    {
        $message = "hello $this->name!";
        return compact('message');
    }
}
