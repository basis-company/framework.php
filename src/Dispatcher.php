<?php

namespace Basis;

use Exception;
use LinkORB\Component\Etcd\Client;

class Dispatcher
{
    private $etcd;

    public function __construct(Client $etcd)
    {
        $this->etcd = $etcd;
    }

    public function dispatch($job, $params = [])
    {
        $this->etcd->setRoot('jobs');

        $service = $this->etcd->get($job);
        if(!$service) {
            throw new Exception("No service for job $job");
        }

        $host = getenv(strtoupper($service).'_SERVICE_HOST') ?: $service;

        $content = http_build_query([
            'rpc' => json_encode([
                'job' => $job,
                'params' => $params,
            ])
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $content,
            ],
        ]);

        $contents = file_get_contents("http://$host/api", false, $context);

        $result = json_decode($contents);
        if(!$result || !$result->success) {
            throw new Exception($result->message ?: $contents);
        }

        return $result->data;
    }
}
