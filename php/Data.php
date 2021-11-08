<?php

namespace Basis;

use Basis\Data\Crud;
use Exception;
use Tarantool\Client\Client;

class Data
{
    private Client $client;

    public function __construct(Dispatcher $dispatcher)
    {
        $uri = getenv('DATA_CONNECTION');

        if (!$uri) {
            $hostname = $dispatcher->getServiceName() . '-data';
            $resolve = $dispatcher->dispatch('resolve.address', [ 'name' => $hostname ]);
            $port = getenv('DATA_PORT') ?: 3301;
            $uri = 'tcp://' . $resolve->host . ':' . $port;
        }

        $this->client = Client::fromOptions([
            'uri' => $uri,
            'username' => getenv('DATA_USERNAME') ?: 'admin',
            'password' => getenv('DATA_PASSWORD') ?: 'password',
        ]);
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
        return new Crud($this, $space);
    }
}
