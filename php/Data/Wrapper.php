<?php

namespace Basis\Data;

use Tarantool\Client\Client;

class Wrapper
{
    private array $crud = [];

    public function __construct(private string $service, private Client $client)
    {
    }

    public function call($function, ...$args)
    {
        $response = $this->getClient()->call($function, ...$args);

        [$result, $err] = $response;

        if ($err) {
            throw new Exception($err);
        }

        return $result;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getCrud(string $space): Crud
    {
        if (!array_key_exists($space, $this->crud)) {
            $this->crud[$space] = new Crud($this, $space);
        }

        return $this->crud[$space];
    }

    public function getService(): string
    {
        return $this->service;
    }
}
