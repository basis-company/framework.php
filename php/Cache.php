<?php

namespace Basis;

use DateTime;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class Cache
{
    private AdapterInterface $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function wrap($key, callable $callback)
    {
        if ($this->exists($key)) {
            return $this->get($key);
        } else {
            // unset ids [key]
            $this->adapter->reset(false);
        }

        $result = $callback();

        if (property_exists($result, 'expire')) {
            $this->set($key, $result, $result->expire);
        }

        return $result;
    }

    public function get($key)
    {
        $item = $this->getItem($key);
        return $this->getItem($key)->get();
    }

    public function set($key, $value, $expire = null)
    {
        $item = $this->getItem($key);

        if ($expire) {
            $item->expiresAt(DateTime::createFromFormat('U', (int) $expire));
        }

        $item->set($value);
        $this->adapter->save($item);
    }

    public function exists($key): bool
    {
        $key = $this->getKey($key);
        return $this->adapter->hasItem($key);
    }

    public function delete($key)
    {
        $key = $this->getKey($key);
        return $this->adapter->delete($key);
    }

    public function clear(): self
    {
        $this->adapter->clear();
        return $this;
    }

    public function getItem($key): CacheItemInterface
    {
        $key = $this->getKey($key);
        return $this->adapter->getItem($key);
    }

    public function getKey(): string
    {
        if (func_num_args() == 0) {
            return 'empty';
        }

        if (func_num_args() == 1 && is_string(func_get_arg(0))) {
            return func_get_arg(0);
        }

        return md5(json_encode(func_get_args()));
    }
}
