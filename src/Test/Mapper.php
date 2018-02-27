<?php

namespace Basis\Test;

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
}
