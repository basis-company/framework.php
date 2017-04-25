<?php

namespace Basis\Jobs\Module;

use Basis\Config;
use Basis\Runner;
use LinkORB\Component\Etcd\Client;

class Bootstrap
{
    public function run(Runner $runner, Client $client, Config $config)
    {
        $runner->dispatch('tarantool.migrate');

        $client->setRoot('services');
        if(!$client->get($config['name'])) {
            $client->set($config['name'])
        }
        $client->setRoot('jobs');
        $meta = $runner->dispatch('module.meta');
        foreach($meta['jobs'] as $job) {
            if(!$client->get($job)) {
                $client->set($job, $config['name']);
            }
        }
    }
}
