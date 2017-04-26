<?php

namespace Basis\Jobs\Module;

use Basis\Config;
use Basis\Event;
use Basis\Runner;
use Exception;
use LinkORB\Component\Etcd\Client;

class Bootstrap
{
    public function run(Runner $runner, Client $client, Config $config, Event $event)
    {
        $runner->dispatch('tarantool.migrate');

        $this->store($client, 'services/'.$config['name']);

        $meta = $runner->dispatch('module.meta');
        foreach($meta['jobs'] as $job) {
            $this->store($client, 'jobs/'.$job, $config['name']);
        }

        if($config->offsetExists('events') && is_array($config['events'])) {
            foreach($config['events'] as $nick => $listeners) {
                $listeners = (array) $listeners;
                foreach($listeners as $listener) {
                    $event->subscribe($nick, $listener);
                }
            }
        }
    }

    private function store(Client $client, $path, $value = null)
    {
        list($folder, $key) = explode('/', $path);

        $client->setRoot($folder);
        try {
            $client->ls('.');
        } catch(Exception $e) {
            $client->mkdir('.');
        }

        try {
            $client->get($key);
        } catch(Exception $e) {
            $client->set($key, $value);
        }
    }
}
