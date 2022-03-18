<?php

namespace Basis\Controller;

use Basis\Dispatcher;

class Restart
{
    public function index(Dispatcher $dispatcher)
    {
        touch('var/restart');
        $dispatcher->dispatch('module.register');
    }
}
