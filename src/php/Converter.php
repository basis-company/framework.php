<?php

namespace Basis;

class Converter
{
public     function toObject($data)
    {
        if(is_array($data)) {
            if(array_keys($data) === range(0, count($data) -1)) {
                foreach($data as $k => $v) {
                    $data[$k] = $this->toObject($v);
                }
                return $data;
            }
        }

        $data = (object) $data;

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data->$k = $this->toObject($v);
            }
        }

        return $data;
    }

    public function toArray($data)
    {
        if (!$data) {
            return [];
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data[$k] = $this->toArray($v);
            }
        }

        return $data;
    }
}