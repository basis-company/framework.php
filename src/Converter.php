<?php

namespace Basis;

use stdClass;
use Exception;

class Converter
{
    protected function isTuple($array)
    {
        return !count($array) || array_keys($array) === range(0, count($array) -1);
    }

    public function toObject($data) : stdClass
    {
        if (is_array($data)) {
            if ($this->isTuple($data)) {
                throw new Exception('Tuple should not be converted to object');
            }
        }

        $data = (object) $data;

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                if (is_array($v) && $this->isTuple($v)) {
                    $array = [];
                    foreach ($v as $vk => $vv) {
                        if (is_object($vv) || is_array($vv)) {
                            $array[$vk] = $this->toObject($vv);
                        } else {
                            $array[$vk] = $vv;
                        }
                    }
                    $data->$k = $array;
                } else {
                    $data->$k = $this->toObject($v);
                }
            }
        }

        return $data;
    }

    public function toArray($data) : array
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

    private $underscores = [];

    public function toUnderscore(string $input) : string
    {
        if (!array_key_exists($input, $this->underscores)) {
            preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
            $ret = $matches[0];
            foreach ($ret as &$match) {
                $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
            }
            $this->underscores[$input] = implode('_', $ret);
        }
        return $this->underscores[$input];
    }

    private $camelcased = [];

    public function toCamelCase(string $string, bool $capitalize = false) : string
    {
        $capitalize = $capitalize ? 1 : 0;

        if (!array_key_exists($capitalize, $this->camelcased)) {
            $this->camelcased[$capitalize] = [];
        }

        if (!array_key_exists($string, $this->camelcased[$capitalize])) {
            $chain = explode('_', $string);
            foreach ($chain as $index => $word) {
                $chain[$index] = $index || $capitalize ? ucfirst($word) : $word;
            }

            $this->camelcased[$capitalize][$string] = implode('', $chain);
        }

        return $this->camelcased[$capitalize][$string];
    }
}
