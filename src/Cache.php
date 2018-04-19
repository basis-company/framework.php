<?php

namespace Basis;

use Basis\Converter;
use Carbon\Carbon;

class Cache
{
    private $converter;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
        if (!is_dir('.cache')) {
            mkdir('.cache');
            chown('.cache', 'www-data');
            chgrp('.cache', 'www-data');
        }
    }

    private $cache = [];

    public function exists($key)
    {
        $filename = '.cache/'.$key;
        if (file_exists($filename)) {
            if (!array_key_exists($key, $this->cache)) {
                $this->cache[$key] = include $filename;
            }
            return $this->cache[$key]['expire'] > Carbon::now()->getTimestamp();
        }
    }

    public function get($key)
    {
        if ($this->exists($key)) {
            return $this->converter->toObject($this->cache[$key]);
        }
    }

    public function set($key, $value)
    {
        $filename = '.cache/'.$key;
        $data = $this->converter->toArray($value);
        $string = '<?php return '.var_export($data, true).';';

        file_put_contents($filename, $string);
        chown($filename, 'www-data');
        chgrp($filename, 'www-data');

        $this->cache[$key] = $data;
    }

    public function wrap($key, $callback)
    {
        $hash = md5(json_encode($key));
        if ($this->exists($hash)) {
            return $this->get($hash);
        }
        $result = call_user_func($callback);
        $expire = null;
        if (is_array($result) && array_key_exists('expire', $result)) {
            $expire = $result['expire'];
        } elseif (is_object($result) && property_exists($result, 'expire')) {
            $expire = $result->expire;
        }
        if ($expire && $expire > Carbon::now()->getTimestamp()) {
            $this->set($hash, $result);
        }
        return $result;
    }
}
