<?php

namespace Basis;

use Exception;
use LinkORB\Component\Etcd\Client;

class Service
{
    private $client;
    private $name;

    public function __construct(Client $client, Config $config)
    {
        $this->client = $client;
        $this->name = $config['service'];
        if (!$this->name) {
            throw new Exception("No service defined in config");
        }
    }

    public function eventExists($event)
    {
        return $this->exists("events/$event");
    }

    public function getName()
    {
        return $this->name;
    }

    public function registerJob($job, $params)
    {
        $this->store("jobs/$job/params", json_encode($params));
        $this->store("jobs/$job/service", $this->name);
    }

    public function subscribe($event)
    {
        $this->store("events/$event/$this->name");
    }

    public function unsibscribe($event)
    {
        $this->remove("events/$event/$this->name");
    }

    public function updateAssetsVersion($hash)
    {
        $this->store("assets/$this->name", $hash);
    }

    private function exists($path)
    {
        $chain = explode('/', $path);
        $key = array_pop($chain);
        $folder = implode('/', $chain);

        try {
            $this->client->setRoot($folder);
            $this->client->get($key);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    private function store($path, $value = null)
    {
        $chain = explode('/', $path);

        $key = array_pop($chain);
        $folder = implode('/', $chain);

        $this->client->setRoot($folder);
        try {
            $this->client->ls('.');
        } catch (Exception $e) {
            $this->client->mkdir('.');
        }

        try {
            if ($this->client->get($key) != $value) {
                $this->client->set($key, $value);
            }
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            $this->client->mkdir('.');
        }

        try {
            $this->client->remove($key);
        } catch (Exception $e) {
        }
    }
}
