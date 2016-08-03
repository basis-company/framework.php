<?php

namespace Basis;

class Framework extends Filesystem
{
    protected $root;

    function __construct()
    {
        $this->root = dirname(dirname(__DIR__));
    }

    function completeClassName($namespace)
    {
        return "Basis\\$namespace";
    }
}
