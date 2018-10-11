<?php

namespace Basis;

use Carbon\Carbon;

class Cache
{
    private $cache = [];
    private $converter;

    public function __construct(Filesystem $fs, Converter $converter)
    {
        $this->converter = $converter;
        $this->path = $fs->getPath('.cache');
        if (!is_dir($this->path)) {
            mkdir($this->path);
            @chmod($this->path, 0777);
        }
    }


    public function clear()
    {
        foreach (scandir($this->path) as $f) {
            if ($f != '.' && $f != '..') {
                unlink($this->path.'/'.$f);
            }
        }
    }

    public function exists($key)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key]['expire'] > Carbon::now()->getTimestamp();
        }
        $filename = $this->path . '/' . $key;
        if (file_exists($filename) && filesize($filename)) {
            if (!array_key_exists($key, $this->cache)) {
                $this->cache[$key] = include $filename;
            }
            $value = $this->cache[$key];
            return !array_key_exists('expire', $value) || $value['expire'] > Carbon::now()->getTimestamp();
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
        @chmod($filename, 0777);

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
