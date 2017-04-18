<?php

namespace Basis;

class Framework extends Filesystem
{
    protected $root;
    protected $namespace = 'Basis';

    public function __construct()
    {
        $this->root = dirname(__DIR__);
    }
}
