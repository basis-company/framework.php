<?php

namespace Basis;

use Exception;
use LinkORB\Component\Etcd\Client;

class Service
{
    private $client;
    private $name;

    public function __construct($name, Client $client)
    {
        $this->client = $client;
        $this->name = $name;
    }

    public function eventExists(string $event) : bool
    {
        return $this->exists("events/$event");
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function register()
    {
        $this->store("services/$this->name");
    }

    public function listServices() : array
    {
        $this->client->setRoot('services');

        $services = [];
        foreach ($this->client->ls() as $entry) {
            $name = substr($entry, strlen('/services/'));
            if ($name) {
                $services[] = $name;
            }
        }

        return $services;
    }

    public function registerRoute(string $route)
    {
        $this->store("routes/$route", $this->name);
    }

    public function subscribe(string $event)
    {
        $this->store("events/$event/$this->name");
    }

    public function unsubscribe(string $event)
    {
        $this->remove("events/$event/$this->name");
    }

    private function exists(string $path) : bool
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

    private function store(string $path, $value = null)
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

    private function remove(string $path)
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
