<?php

namespace Basis\Jobs\Module;

use Basis\Conig;
use Basis\Runner;
use LinkORB\Component\Etcd\Client;

class Bootstrap
{
    public function run(Runner $runner, Client $client, Config $config)
    {
        $runner->dispatch('tarantool.migrate');

        $meta = $runner->dispatch('module.meta');
        $client->set('/service/'.$config['name'], $meta);
    }
}