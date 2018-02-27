<?php

namespace Basis\Test;

use Exception;

class Mapper
{
    protected $data;

    public function __construct($data)
    {
        $this->data = [];
        foreach ($data as $space => $rows) {
            $this->data[$space] = [];
            foreach ($rows as $row) {
                $this->data[$space][] = (object) $row;
            }
        }
    }

    public function find(string $space, $params = [])
    {
        if (array_key_exists($space, $this->data)) {
            $data = $this->data[$space];
            if (count($params)) {
                foreach ($data as $i => $v) {
                    if (array_intersect_assoc($params, get_object_vars($v)) != $params) {
                        unset($data[$i]);
                    }
                }
                $data = array_values($data);
            }
            return $data;
        }
        return [];
    }

    public function findOne(string $space, $params = [])
    {
        if (array_key_exists($space, $this->data)) {
            foreach ($this->data[$space] as $candidate) {
                if (!count($params) || array_intersect_assoc($params, get_object_vars($candidate)) == $params) {
                    return $candidate;
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
