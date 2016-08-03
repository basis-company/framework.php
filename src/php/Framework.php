<?php

namespace Basis;

class Framework extends Filesystem
{
    private $root;

    function __construct()
    {
        $this->root = dirname(dirname(__DIR__));
    }
    protected function completeNamespace($namespace)
    {
        return "Basis\\$namespace";
    }
}
