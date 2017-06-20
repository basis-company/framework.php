<?php

namespace Basis;

use ArrayAccess;

class Config implements ArrayAccess
{
    private $converter;

    public function __construct(Framework $framework, Filesystem $fs, Converter $converter)
    {
        $this->converter = $converter;

        $filename = $fs->getPath("config.php");
        if (!file_exists($filename)) {
            $filename = $framework->getPath('resources/default/config.php');
        }
        $data = include $filename;
        foreach ($data as $k => $v) {
            $this->$k = $v;
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

    public function shouldHave($offset)
    {
        if (!isset($this[$offset])) {
            throw new Exception("No offset $offset");
        }
    }

    private function getNode($offset, $createIfNone = false)
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
                        return;
                    }
                }
                $current = $current->$key;
            }
        }

        return $current;
    }
}
