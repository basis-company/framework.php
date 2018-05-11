<?php

namespace Basis;

use Carbon\Carbon;

class Cache
{
    private $converter;

    public function __construct(Filesystem $fs, Converter $converter)
    {
        $this->converter = $converter;
        $this->path = $fs->getPath('.cache');
        if (!is_dir($this->path)) {
            mkdir($this->path);
            @chown($this->path, 'www-data');
            @chgrp($this->path, 'www-data');
        }
    }

    private $cache = [];

    public function exists($key)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key]['expire'] > Carbon::now()->getTimestamp();
        }
        $filename = $this->path . '/' . $key;
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
        $filename = $this->path . '/' . $key;
        $data = $this->converter->toArray($value);
        $string = '<?php return '.var_export($data, true).';';

        file_put_contents($filename, $string);
        @chown($filename, 'www-data');
        @chgrp($filename, 'www-data');

        $this->cache[$key] = $data;
    }

    public function wrap($key, $callback)
    {
        if (!is_string($key)) {
            $key = md5(json_encode($key));
        }
        if ($this->exists($key)) {
            return $this->get($key);
        }
        $result = call_user_func($callback);
        $expire = null;
        if (is_array($result) && array_key_exists('expire', $result)) {
            $expire = $result['expire'];
        } elseif (is_object($result) && property_exists($result, 'expire')) {
            $expire = $result->expire;
        }
        if ($expire && $expire > Carbon::now()->getTimestamp()) {
            $this->set($key, $result);
        }
        return $result;
    }
}
