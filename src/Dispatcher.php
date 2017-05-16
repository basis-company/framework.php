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

    public function dispatch($job, $params = [], $host = null)
    {

        if(!$host) {

            $this->etcd->setRoot('jobs/'.$job);

            try {
                $service = $this->etcd->get('service');
            } catch(Exception $e) {
                $service = null;
            }
            if(!$service) {
                throw new Exception("No service for job $job");
            }

            $host = $service;

            $this->etcd->setRoot('services/' . $service);
            try {
                $hostname = $this->etcd->get('host');
                if($hostname && getenv($hostname)) {
                    $host = getenv($hostname).':'.getenv($this->etcd->get('port'));
                }
            } catch(Exception $e) {
                throw new Exception("No service $service");
            }
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

        $contents = file_get_contents("http://$host/api", false, $context);

        $result = json_decode($contents);
        if(!$result || !$result->success) {
            throw new Exception($result->message ?: $contents);
        }

        return $result->data;
    }
}
