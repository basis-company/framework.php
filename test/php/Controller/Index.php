<?php

namespace Controller;

use Basis\Toolkit;
use Basis\Runner;

class Index
{
    use Toolkit;

    public function index()
    {
        return 'index page';
    }

    public function hello(Runner $runner)
    {
        return $runner->dispatch('test.hello')->message;
    }
}
