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

    public function hello()
    {
        return $this->dispatch('test.hello')->message;
    }
}
