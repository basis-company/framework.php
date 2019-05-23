<?php

namespace Job;

use Basis\Job;

/**
 * Example job for greeting
 */
class Hello extends Job
{
    public $name = 'world';

    public function run(HelloSomebody $dependency)
    {
        if ($this->name === 'bazyaba') {
            $this->confirm('bazyaba?');
        }
        $message = "hello $this->name!";
        return compact('message');
    }
}
