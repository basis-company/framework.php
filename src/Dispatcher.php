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

    public function dispatch($job, $params = [], $service = null)
    {
        if (!$service) {
            $service = explode('.', $job)[0];
        }

        $content = http_build_query([
            'rpc' => json_encode([
                'job' => $job,
                'params' => $params,
            ])
        ]);


        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode([
                    'content-type: application/x-www-form-urlencoded',
                    'x-real-ip: '.$_SERVER['HTTP_X_REAL_IP'],
                    'x-session: '.$_SERVER['HTTP_X_SESSION'],
                ], "\r\n"),
                'content' => $content,
            ],
        ]);

        $contents = file_get_contents("http://$service/api", false, $context);

        $result = json_decode($contents);
        if (!$result || !$result->success) {
            throw new Exception($result->message ?: $contents);
        }

        return $result->data;
    }
}
