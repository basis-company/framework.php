<?php

namespace Basis;

use Carbon\Carbon;
use Exception;
use stdClass;
use Tarantool\Mapper\Entity;

class Converter
{
    public function isTuple($array)
    {
        if (is_object($array)) {
            $array = get_object_vars($array);
        }
        if (!is_array($array)) {
            return false;
        }
        if (!count($array)) {
            return true;
        }
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            return array_key_last($array) === count($array) - 1;
        }
        return array_values($array) == $array;
    }

    public function toObject($data)
    {
        if (is_array($data)) {
            if ($this->isTuple($data)) {
                return $this->convertArrayToObject($data);
            }
        }

        if (is_object($data)) {
            if ($data instanceof Entity) {
                // keep instance
                return $data;
            }

            $tmp = $data;
            $data = [];
            foreach ($tmp as $k => $v) {
                $data[$k] = $v;
            }
        }

        $data = (object) $data;

        foreach ($data as $k => $v) {
            if (is_array($v) && $this->isTuple($v)) {
                $data->$k = $this->convertArrayToObject($v);
            } elseif (is_array($v) || is_object($v)) {
                $data->$k = $this->toObject($v);
            }
        }

        return $data;
    }

    public function convertArrayToObject($data)
    {
        $result = [];
        foreach ($data as $k => $v) {
            if ($this->isTuple($v)) {
                $result[$k] = $this->convertArrayToObject($v);
            } elseif (is_object($v) || is_array($v)) {
                $result[$k] = $this->toObject($v);
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public function toArray($data): array
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

    public function toUnderscore(string $input): string
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

    public function toCamelCase(string $string, bool $capitalize = false): string
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


    private $dates = [];

    public function getDate($string)
    {
        $key = $string;
        if (func_num_args() == 3) {
            $key = implode('.', func_get_args());
        }

        if (Carbon::hasTestNow() || !array_key_exists($key, $this->dates)) {
            if (strlen($string) == 8 && is_numeric($string)) {
                $value = Carbon::createFromFormat('Ymd', $string)->setTime(0, 0, 0);
            } elseif (func_num_args() == 3) {
                [$year, $month, $day] = func_get_args();
                $value = Carbon::createFromDate($year, $month, $day)->setTime(0, 0, 0);
            } else {
                $value = Carbon::parse($string);
            }
            if (Carbon::hasTestNow()) {
                return $value;
            }
            $this->dates[$key] = $value;
        }
        return $this->dates[$key]->copy();
    }

    public function getTimestamp($string)
    {
        return $this->getDate($string)->timestamp;
    }

    public function getPluralForm(int $n, array $forms): string
    {
        if (is_float($n)) {
            return $forms[1];
        }
        if ($n % 10 == 1 && $n % 100 != 11) {
            return $forms[0];
        }
        if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
            return $forms[1];
        }
        return $forms[2];
    }

    public function xtypeToClass(string $xtype): string
    {
        return implode('\\', array_map('ucfirst', explode('.', $xtype)));
    }

    public function classToXtype(string $class): string
    {
        return implode('.', array_map('strtolower', explode('\\', $class)));
    }
}
