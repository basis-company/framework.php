<?php

namespace Basis;

use Exception;
use GuzzleHttp\Client;

class Dispatcher
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service === null) {
            $service = explode('.', $job)[0];
        }

        $response = $this->client->post("http://$service/api", [
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
            throw new Exception("Host $service unreachable");
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
