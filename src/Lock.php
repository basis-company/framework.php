<?php

namespace Basis;

use Exception;
use Predis\Client;

class Lock
{
    private $redis;
    private $locks = [];

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
        register_shutdown_function([$this, 'releaseLocks']);
    }

    public function acquire($name, $ttl = 1)
    {
        while (!$this->lock($name, $ttl)) {
            $this->waitUnlock($name);
        }
        return true;
    }

    public function exists($name)
    {
        return $this->redis->get("lock-$name") !== null;
    }

    public function lock($name, $ttl = 1)
    {
        if (in_array($name, $this->locks)) {
            throw new Exception("Lock $name was already registered");
        }
        $result = $this->redis->set("lock-$name", 1, 'EX', $ttl, 'NX');
        if ($result) {
            $this->locks[] = $name;
            return true;
        }
        return false;
    }

    public function releaseLocks()
    {
        foreach ($this->locks as $name) {
            $this->unlock($name);
        }
    }

    public function unlock($name)
    {
        if (!in_array($name, $this->locks)) {
            throw new Exception("Lock $name was already registered");
        }
        
        foreach ($this->locks as $i => $candidate) {
            if ($candidate == $name) {
                unset($this->locks[$i]);
                $this->redis->del("lock-$name");
                break;
            }
        }

        $this->locks = array_values($this->locks);
    }

    public function waitUnlock($name)
    {
        while ($this->exists($name)) {
            usleep(100);
        }
        return true;
    }
}
