<?php

namespace Basis\Test;

use Basis\Test;
use Exception;
use Tarantool\Mapper\Entity as MapperEntity;
use Tarantool\Mapper\Mapper as MapperMapper;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Repository as MapperRepository;

class Mapper extends MapperMapper
{
    protected $test;
    public $serviceName;

    public function __construct(Test $test, $service)
    {
        $this->test = $test;
        $this->serviceName = $service;
    }

    public function create(string $space, $params): MapperEntity
    {
        $key = $this->serviceName . '.' . $space;
        if (!array_key_exists($key, $this->test->data)) {
            throw new Exception("No data container for $key");
        }
        $instance = new Entity($this->test, $key);
        foreach ($params as $k => $v) {
            $instance->$k = $v instanceof MapperEntity ? $v->id : $v;
        }
        return $instance;
    }

    public function find(string $space, $params = []): array
    {
        $key = $this->serviceName . '.' . $space;
        if (array_key_exists($key, $this->test->data)) {
            $data = $this->test->data[$key];
            foreach ($data as $i => $v) {
                if (count($params) && array_intersect_assoc($params, get_object_vars($v)) != $params) {
                    unset($data[$i]);
                    continue;
                }
                $data[$i] = (object) $v;
            }
            $data = array_values($data);
            return $data;
        }
        return [];
    }

    public function findOne(string $space, $params = []): ?MapperEntity
    {
        $key = $this->serviceName . '.' . $space;
        if (is_numeric($params) || is_string($params)) {
            $params = [ 'id' => $params ];
        }
        if (array_key_exists($key, $this->test->data)) {
            foreach ($this->test->data[$key] as $candidate) {
                if (!count($params) || array_intersect_assoc($params, get_object_vars($candidate)) == $params) {
                    return (object) $candidate;
                }
            }
        }
        return null;
    }

    public function findOrCreate(string $space, $params = [], $data = []): MapperEntity
    {
        if (!$this->findOne($space, $params)) {
            $this->create($space, $data ?: $params)->save();
        }
        return $this->findOne($space, $params);
    }

    public function findOrFail(string $space, $params = []): MapperEntity
    {
        $result = $this->findOne($space, $params);
        if (!$result) {
            throw new Exception("No " . $space . ' found using ' . json_encode($params));
        }
        return $result;
    }

    public function getPlugin($class)
    {
        if ($class == Spy::class) {
            return new class {
                public function hasChanges()
                {
                    return false;
                }
            };
        }
    }

    protected $repositores = [];

    public function getRepository(string $space): MapperRepository
    {
        if (!array_key_exists($space, $this->repositores)) {
            $this->repositores[$space] = new Repository($this, $space);
        }
        return $this->repositores[$space];
    }

    public function remove($space, $params = []): MapperMapper
    {
        if (is_object($params)) {
            $params = get_object_vars($params);
        }
        $key = $this->serviceName . '.' . $space;
        if (array_key_exists($key, $this->test->data)) {
            foreach ($this->test->data[$key] as $i => $v) {
                if (count($params) && array_intersect_assoc($params, get_object_vars($v)) == $params) {
                    unset($this->test->data[$key][$i]);
                }
            }
        }

        return $this;
    }
}
