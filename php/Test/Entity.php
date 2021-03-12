<?php

namespace Basis\Test;

use Basis\Test;
use Tarantool\Mapper\Entity as MapperEntity;

class Entity extends MapperEntity
{
    public $id;

    private Test $testReference;
    private string $entityKey;

    public function __construct(Test $test, string $key)
    {
        $this->testReference = $test;
        $this->entityKey = $key;
    }

    public function save(): MapperEntity
    {
        if (!$this->id) {
            $max = rand(100, 1000) * 100;
            if (count($this->testReference->data[$this->entityKey])) {
                $max = max(array_keys($this->testReference->data[$this->entityKey]));
            }
            $this->id = $max + 1;
        }
        $this->testReference->data[$this->entityKey][$this->id] = $this;
        return $this;
    }

    public function __debugInfo()
    {
        $info = parent::__debugInfo();
        unset($info['testReference']);
        unset($info['entityKey']);
        return $info;
    }
}
