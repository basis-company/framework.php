<?php

namespace Basis;

use ArrayAccess;
use Closure;

class Config implements ArrayAccess
{
    protected $app;
    protected $converter;

    public function __construct(Application $app, Framework $framework, Filesystem $fs, Converter $converter)
    {
        $this->app = $app;
        $this->converter = $converter;

        $filename = $fs->getPath("config.php");
        if (!file_exists($filename)) {
            $filename = $framework->getPath('resources/default/config.php');
        }
        $data = include $filename;
        foreach ($data as $k => $v) {
            $this->$k = $v;
            if ($v instanceof Closure) {
                continue;
            }
            if (is_array($v) || is_object($v)) {
                $this->$k = $converter->toObject($v);
            }
        }
    }

    public function offsetExists($offset)
    {
        return $this->getNode($offset) != null;
    }

    public function offsetGet($offset)
    {
        $value = $this->getNode($offset);
        if (!is_object($value)) {
            return $value;
        }

        return $this->converter->toArray($value);
    }

    public function offsetSet($offset, $value)
    {
        $path = explode('.', $offset);
        $key = array_pop($path);
        $parent = $this->getNode(implode('.', $path), true);
        if (is_array($value) || is_object($value)) {
            $value = $this->converter->toObject($value);
        }

        return $parent->$key = $value;
    }

    public function offsetUnset($offset)
    {
        $path = explode('.', $offset);
        $key = array_pop($path);
        $parent = $this->getNode(implode('.', $path));
        unset($parent->$key);
    }

    public function require(string $offset)
    {
        if (!isset($this[$offset])) {
            throw new Exception("No offset $offset");
        }
    }

    private function getNode(string $offset, bool $createIfNone = false)
    {
        if (!$offset) {
            return $this;
        }
        $current = $this;
        foreach (explode('.', $offset) as $key) {
            if (is_object($current)) {
                if (!property_exists($current, $key)) {
                    if ($createIfNone) {
                        $current->$key = (object) [];
                    } else {
                        return null;
                    }
                }
                if ($current->$key instanceof Closure) {
                    $callback = $current->$key;
                    $v = $callback($this->app);

                    if (is_array($v) || is_object($v)) {
                        $v = $this->converter->toObject($v);
                    }
                    $current->$key = $v;
                }

                $current = $current->$key;
            }
        }

        return $current;
    }
}
