<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Lock\LockFactory;
use Tarantool\Client\Client;
use Tarantool\SymfonyLock\TarantoolStore;

class Lock
{
    public function init(Container $container)
    {
        $container->share(LockFactory::class, function () use ($container) {
            $store = $container->get(TarantoolStore::class);
            return new LockFactory($store);
        });

        $container->share(TarantoolStore::class, function () use ($container) {
            $redis = $container->get(Client::class);
            return new TarantoolStore($redis, [
                'createSchema' => true,
                'space' => 'basis_lock',
            ]);
        });
    }
}
