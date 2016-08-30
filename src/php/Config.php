<?php

namespace Basis;

use ArrayAccess;
use Dotenv\Dotenv;

class Config implements ArrayAccess
{
    private $converter;

    public function __construct(Filesystem $fs, Converter $converter)
    {
        $this->converter = $converter;

        if($fs->exists('.env')) {
            $dotenv = new Dotenv($fs->getPath());
            $dotenv->load();
        }

        foreach ($fs->listFiles('resources/config') as $file) {
            $values = $this->converter->toObject(include $fs->getPath("resources/config/$file"));
            $current = $this;
            $name = substr($file, 0, -4);
            if (stristr($name, '/')) {
                $namespace = explode('/', $name);
                $name = array_pop($namespace);
                foreach ($namespace as $key) {
                    $current->$key = (object) [];
                    $current = $current->$key;
                }
            }
            $current->$name = $values;
        }
    }

    public function offsetExists($offset)
    {
        return $this->getNode($offset) != null;
    }

    public function offsetGet($offset)
    {
        $value = $this->getNode($offset);
        if(!is_object($value)) {
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
        if(!isset($this[$offset])) {
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