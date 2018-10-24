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

    public function dispatch(string $job, array $params = [], string $service = null, $context = null)
    {
        return $this->dispatchAsync($job, $params, $service, $context)->wait();
    }

    public function dispatchAsync(string $job, array $params = [], string $service = null, $context = null)
    {
        if ($service === null) {
            $service = $this->getServiceName($job);
        }

        $url = $this->getUrl($service, $job);
        if (is_array($params) && array_key_exists('eventId', $params)) {
            $url .= '/'.$params['eventId'];
        }

        if (!$context) {
            $context = $this->getContext();
        }

        $response = $this->client->postAsync($url, [
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

        return $response->then(function ($response) {
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
        });
    }

    protected function getContext() : array
    {
        $context = get_object_vars($this->context);
    
        if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            $context['host'] = $_SERVER['HTTP_X_REAL_IP'];
        }

        if (array_key_exists('HTTP_X_SESSION', $_SERVER)) {
            $context['session'] = $_SERVER['HTTP_X_SESSION'];
        }

        return $context;
    }

    protected function getServiceName(string $job) : string
    {
        return explode('.', $job)[0];
    }

    protected function getUrl(string $service, string $job) : string
    {
        $host = $this->service->getHost($service)->address;
        return "http://$host/api/" . str_replace('.', '/', $job);
    }

    public function send(string $job, array $params = [], string $service = null, $context = null) : boolean
    {
        if ($service === null) {
            $service = $this->getServiceName($job);
        }

        $url = $this->getUrl($service, $job);
        if (is_array($params) && array_key_exists('eventId', $params)) {
            $url .= '/'.$params['eventId'];
        }

        if (!$context) {
            $context = $this->getContext();
        }

        $rpc = [
            'context' => $context,
            'job'     => $job,
            'params'  => $params,
        ];

        $content = 'rpc='.urlencode(json_encode($rpc));

        $parts = parse_url($url);
        $port = isset($parts['port']) ? $parts['port'] : 80;
        $fp = fsockopen($parts['host'], $port, $errno, $errstr, 30);

        if (!$fp) {
            return false;
        }

        fwrite($fp, implode("\r\n", [
            "POST " . $parts['path'] . " HTTP/1.1",
            "Host: " . $parts['host'],
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($content),
            "Connection: Close",
            "",
            isset($content) ? $content : '',
        ]));

        fclose($fp);

        return true;
    }
}
