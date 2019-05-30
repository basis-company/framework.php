<?php

namespace Basis\Test;

class Repository
{
    protected $mapper;
    protected $space;

    public function __construct(Mapper $mapper, string $space)
    {
        $this->mapper = $mapper;
        $this->space = $space;
    }

    public function __call($method, $args)
    {
        return $this->mapper->$method($this->space, ...$args);
    }
}
