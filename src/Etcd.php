<?php

namespace Basis;

use Exception;
use LinkORB\Component\Etcd\Client;

class Etcd
{
    private $client;
    private $service;

    public function __construct(Client $client, Config $config)
    {
        $this->client = $client;
        $this->service = $config['service'];
        if(!$this->service) {
            throw new Exception("No service defined in config");
        }
    }

    public function registerService()
    {
        $this->store("services/$this->service/host", strtoupper($this->service).'_SERVICE_HOST');
        $this->store("services/$this->service/port", strtoupper($this->service).'_SERVICE_PORT');
    }

    public function registerJob($job, $params)
    {
        $this->store("jobs/$job/params", json_encode($params));
        $this->store("jobs/$job/service", $this->service);
    }

    public function subscribe($event)
    {
        $this->store("events/$event/$this->service");
    }

    public function unsibscribe($event)
    {
        $this->remove("events/$event/$this->service");
    }

    private function store($path, $value = null)
    {
        $chain = explode('/', $path);

        $key = array_pop($chain);
        $folder = implode('/', $chain);

        $this->client->setRoot($folder);
        try {
            $this->client->ls('.');
        } catch(Exception $e) {
            $this->client->mkdir('.');
        }

        try {
            $this->client->get($key);
        } catch(Exception $e) {
            $this->client->set($key, $value);
        }
    }

    private function remove($path)
    {
        $chain = explode('/', $path);

        $key = array_pop($chain);
        $folder = implode('/', $chain);

        $this->client->setRoot($folder);
        try {
            $this->client->ls('.');
        } catch(Exception $e) {
            $this->client->mkdir('.');
        }

        try {
            $this->client->remove($key);
        } catch(Exception $e) {
        }
    }
}
