<?php

namespace Basis;

class Converter
{
    function toObject($data)
    {
        $data = (object) $data;

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data->$k = $this->toObject($v);
            }
        }

        $array = get_object_vars($data);
        if(array_values($array) == $array) {
            return $array;
        }

        return $data;
    }

    function toArray($data)
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