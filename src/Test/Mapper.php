<?php

namespace Basis\Test;

use Basis\Test;
use Exception;

class Mapper
{
    protected $test;
    public $serviceName;

    public function __construct(Test $test, $service)
    {
        $this->test = $test;
        $this->serviceName = $service;
    }

    public function find(string $space, $params = [])
    {
        $key = $this->serviceName.'.'.$space;
        if (array_key_exists($key, $this->test->data)) {
            $data = $this->test->data[$key];
            foreach ($data as $i => $v) {
                if (count($params) && array_intersect_assoc($params, $v) != $params) {
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

    public function findOne(string $space, $params = [])
    {
        $key = $this->serviceName.'.'.$space;
        if (array_key_exists($key, $this->test->data)) {
            foreach ($this->test->data[$key] as $candidate) {
                if (!count($params) || array_intersect_assoc($params, $candidate) == $params) {
                    return (object) $candidate;
                }
            }
        }
    }

    public function findOrFail(string $space, $params = [])
    {
        $result = $this->findOne($space, $params);
        if (!$result) {
            throw new Exception("No ".$space.' found using '.json_encode($params));
        }
        return $result;
    }
}
