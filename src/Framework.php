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

    public function listClasses(string $namespace = '', string $location = 'src') : array
    {
        return parent::listClasses($namespace, $location);
    }
}
