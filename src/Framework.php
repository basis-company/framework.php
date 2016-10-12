<?php

namespace Basis;

class Framework extends Filesystem
{
    protected $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__);
    }

    public function completeClassName($namespace)
    {
        return "Basis\\$namespace";
    }
}
