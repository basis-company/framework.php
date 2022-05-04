<?php

namespace Basis\Test;

use Tarantool\Mapper\Entity as MapperEntity;
use Tarantool\Mapper\Mapper as MapperMapper;
use Tarantool\Mapper\Repository as MapperRepository;
use Tarantool\Mapper\Space;

class Repository extends MapperRepository
{
    public readonly Mapper $mapper;
    public readonly string $spaceName;

    public readonly Space $space;

    public function __construct(Mapper $mapper, string $spaceName)
    {
        $this->mapper = $mapper;
        $this->spaceName = $spaceName;
    }

    public function create($data): MapperEntity
    {
        return $this->mapper->create($this->spaceName, ...func_get_args());
    }

    public function find($data = [], $one = false): array
    {
        return $this->mapper->find($this->spaceName, ...func_get_args());
    }

    public function findOne($data = []): ?MapperEntity
    {
        return $this->mapper->findOne($this->spaceName, ...func_get_args());
    }

    public function findOrCreate($params = [], $data = []): MapperEntity
    {
        return $this->mapper->findOrCreate($this->spaceName, ...func_get_args());
    }

    public function findOrFail($data = []): MapperEntity
    {
        return $this->mapper->findOrFail($this->spaceName, ...func_get_args());
    }

    public function remove($data = []): self
    {
        $this->mapper->remove($this->spaceName, ...func_get_args());
        return $this;
    }

    public function __call($method, $args)
    {
        return $this->mapper->$method($this->spaceName, ...$args);
    }

    public function getMapper(): MapperMapper
    {
        return $this->mapper;
    }
}
