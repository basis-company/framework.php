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

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service === null) {
            $service = explode('.', $job)[0];
        }

        $content = http_build_query([
            'rpc' => json_encode([
                'job'    => $job,
                'params' => $params,
            ])
        ]);


        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'content' => $content,
                'header'  => implode([
                    'content-type: application/x-www-form-urlencoded',
                    'x-real-ip: '.$_SERVER['HTTP_X_REAL_IP'],
                    'x-session: '.$_SERVER['HTTP_X_SESSION'],
                ], "\r\n"),
                'ignore_errors' => '1'
            ],
        ]);

        $contents = file_get_contents("http://$service/api", false, $context);

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
