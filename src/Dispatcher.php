<?php

namespace Basis;

use Basis\Context;
use Exception;
use GuzzleHttp\Client;

class Dispatcher
{
    protected $client;
    protected $context;
    protected $service;

    public function __construct(Client $client, Context $context, Service $service)
    {
        $this->client = $client;
        $this->context = $context;
        $this->service = $service;
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service === null) {
            $service = explode('.', $job)[0];
        }

        $host = $this->service->getHost($service)->address;
        $url = "http://$host/api/" . str_replace('.', '/', $job);

        $context = get_object_vars($this->context);

        if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            $context['host'] = $_SERVER['HTTP_X_REAL_IP'];
        }

        if (array_key_exists('HTTP_X_SESSION', $_SERVER)) {
            $context['session'] = $_SERVER['HTTP_X_SESSION'];
        }

        $response = $this->client->post($url, [
            'multipart' => [
                [
                    'name' => 'rpc',
                    'contents' => json_encode([
                        'context' => $context,
                        'job'     => $job,
                        'params'  => $params,
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
