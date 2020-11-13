<?php

namespace Basis;

use Exception;
use Symfony\Component\Lock\LockFactory;
use Tarantool\Client\Client;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Schema\Criteria;
use Tarantool\SymfonyLock\TarantoolStore;

class Lock
{
    protected Application $app;
    protected Client $client;
    protected LockFactory $factory;
    protected TarantoolStore $store;

    protected array $locks = [];

    public function __construct(Application $app, Client $client, LockFactory $factory, TarantoolStore $store)
    {
        $this->app = $app;
        $this->factory = $factory;
        $this->client = $client;
        $this->store = $store;
    }

    public function acquireOrQueue($name, $ttl = 60)
    {
        if (!$this->lock($name, $ttl)) {
            if (!$this->lock("queue-$name", $ttl)) {
                // somebody else waiting for next
                return false;
            }
            $this->waitUnlock($name);
            $this->unlock("queue-$name");
            if (!$this->lock($name, $ttl)) {
                // somebody else take lock
                return false;
            }
        }
        return true;
    }

    public function acquire($name, $ttl = 60)
    {
        while (!$this->lock($name, $ttl)) {
            $this->waitUnlock($name);
        }
        return true;
    }

    public function exists($name)
    {
        $lock = $this->factory->createLock($name);
        if ($lock->isAcquired()) {
            // is owned by this process
            return true;
        }

        try {
            $data = $this->client->getSpace('basis_lock')
                ->select(Criteria::key([ $name ]));
            return count($data) > 0 && $data[0][2] >= microtime(true);
        } catch (RequestFailed $e) {
            return false;
        }
    }

    public function lock($name, $ttl = 60)
    {
        $lock = $this->factory->createLock($name, $ttl);

        // overerite if acquired
        if ($lock->acquire()) {
            $this->locks[$name] = $lock;
            return true;
        }

        return false;
    }

    public function releaseLocks()
    {
        foreach ($this->locks as $name => $_) {
            $this->unlock($name);
        }
        
        $this->locks = [];
    }

    public function unlock($name)
    {
        if (!array_key_exists($name, $this->locks)) {
            throw new Exception("Lock $name not found");
        }

        try {
            $this->locks[$name]->release();
        } catch (Exception $e) {
            // ignore exceptions
        }
        unset($this->locks[$name]);
    }

    public function waitUnlock($name)
    {
        while ($this->exists($name)) {
            $this->app->dispatch('module.sleep', [ 'seconds' => 0.1 ]);
        }

        return true;
    }
}
