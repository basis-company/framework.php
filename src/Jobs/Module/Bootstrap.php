<?php

namespace Basis\Jobs\Module;

use Basis\Config;
use Basis\Event;
use Basis\Runner;
use LinkORB\Component\Etcd\Client;

class Bootstrap
{
    public function run(Runner $runner, Client $client, Config $config, Event $event)
    {
        $runner->dispatch('tarantool.migrate');

        $client->setRoot('services');
        if(!$client->get($config['name'])) {
            $client->set($config['name']);
        }

        $client->setRoot('jobs');
        $meta = $runner->dispatch('module.meta');
        foreach($meta['jobs'] as $job) {
            if(!$client->get($job)) {
                $client->set($job, $config['name']);
            }
        }

        if($config->offsetExists('events') && is_array($config['events'])) {
            foreach($config['events'] as $event => $listeners) {
                $listeners = (array) $listeners;
                foreach($listeners as $listener) {
                    $event->subscribe($event, $listener);
                }
            }
        }
    }
}
