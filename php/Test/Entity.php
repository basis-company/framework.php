<?php

namespace Basis\Test;

use Basis\Test;
use Tarantool\Mapper\Entity as MapperEntity;

class Entity extends MapperEntity
{
    public $id;

    private $_test;
    private $_key;
    
    public function __construct(Test $test, string $key)
    {
        $this->_test = $test;
        $this->_key = $key;
    }

    public function save() : MapperEntity
    {
        if (!$this->id) {
            $max = 0;
            if (count($this->_test->data[$this->_key])) {
                $max = max(array_keys($this->_test->data[$this->_key]));
            }
            $this->id = $max + 1;
        }
        $this->_test->data[$this->_key][$this->id] = $this;
        return $this;
    }

    public function __debugInfo()
    {
        $info = parent::__debugInfo();
        unset($info['_test']);
        unset($info['_key']);
        return $info;
    }
}
