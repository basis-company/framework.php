<?php

namespace Basis\Controller;

class Restart
{
    public function index()
    {
        $this->dispatch('module.register');
    }
}
