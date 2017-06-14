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

    public function listClasses($namespace = '', $location = 'src')
    {
        return parent::listClasses($namespace, $location);
    }
}
