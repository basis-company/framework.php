<?php

namespace Basis;

use Exception;
use GuzzleHttp\Client;

class Dispatcher
{
    protected $client;
    protected $service;

    public function __construct(Client $client, Service $service)
    {
        $this->client = $client;
        $this->service = $service;
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service === null) {
            $service = explode('.', $job)[0];
        }

        $host = $this->service->getHost($service)->address;
        $url = "http://$host/api/" . str_replace('.', '/', $job);

        $response = $this->client->post($url, [
            'multipart' => [
                [
                    'name' => 'rpc',
                    'contents' => json_encode([
                        'job'    => $job,
                        'params' => $params,
                    ])
                ]
            ]
        ]);

        $contents = $response->getBody();

        if (!$contents) {
            throw new Exception("Host $host ($service) is unreachable");
        }

        $result = json_decode($contents);
        if (!$result || !$result->success) {
            $exception = new Exception($result->message ?: $contents);
            if ($result->trace) {
                $exception->remoteTrace = $result->trace;
            }
            throw $exception;
        }

        return $result->data;
    }
}
