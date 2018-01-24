<?php

namespace Basis;

use Exception;
use GuzzleHttp\Client;

class Dispatcher
{
    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'transfer-encoding' => 'chunked',
                'x-real-ip' => $_SERVER['HTTP_X_REAL_IP'],
                'x-session' => $_SERVER['HTTP_X_SESSION'],
            ]
        ]);
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
            throw new Exception($result->message ?: $contents);
        }

        return $result->data;
    }
}
