<?php

namespace Basis\Test;

use Tarantool\Mapper\Entity as MapperEntity;
use Tarantool\Mapper\Mapper as MapperMapper;
use Tarantool\Mapper\Repository as MapperRepository;

class Repository extends MapperRepository
{
    protected $mapper;
    protected $space;

    public function __construct(Mapper $mapper, string $space)
    {
        $this->mapper = $mapper;
        $this->space = $space;
    }

    public function create($data): MapperEntity
    {
        return $this->mapper->create($this->space, ...func_get_args());
    }

    public function find($data = [], $one = false): array
    {
        return $this->mapper->find($this->space, ...func_get_args());
    }

    public function findOne($data = []): ?MapperEntity
    {
        return $this->mapper->findOne($this->space, ...func_get_args());
    }

    public function findOrCreate($params = [], $data = []): MapperEntity
    {
        return $this->mapper->findOrCreate($this->space, ...func_get_args());
    }

    public function findOrFail($data = []): MapperEntity
    {
        return $this->mapper->findOrFail($this->space, ...func_get_args());
    }

    public function remove($data = []): self
    {
        $this->mapper->remove($this->space, ...func_get_args());
        return $this;
    }

    public function __call($method, $args)
    {
        return $this->mapper->$method($this->space, ...$args);
    }

    public function getMapper(): MapperMapper
    {
        return $this->mapper;
    }
}
