<?php

namespace Basis;

use Basis\Context;
use Exception;
use GuzzleHttp\Client;
use OpenTelemetry\Tracing\Tracer;

class Dispatcher
{
    use Toolkit;

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

        $span = $this->get(Tracer::class)
            ->getActiveSpan()
            ->getSpanContext();

        $response = $this->get(Client::class)->postAsync($url, [
            'multipart' => [
                [
                    'name' => 'rpc',
                    'contents' => json_encode([
                        'job'     => $job,
                        'params'  => $params,
                        'context' => $context,
                        'span' => [
                            'traceId' => $span->getTraceId(),
                            'spanId' => $span->getSpanId(),
                        ],
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
        $context = get_object_vars($this->get(Context::class));
    
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
        $host = $this->get(Service::class)->getHost($service)->address;
        return "http://$host/api/" . str_replace('.', '/', $job);
    }

    public function send(string $job, array $params = [], string $service = null, $context = null)
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

        $parts = [
            "POST " . $parts['path'] . " HTTP/1.1",
            "Host: " . $parts['host'],
        ];

        if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            $parts[] = "X-Real-Ip: ".$_SERVER['HTTP_X_REAL_IP'];
        }
        if (array_key_exists('HTTP_X_SESSION', $_SERVER)) {
            $parts[] = "X-Session: ".$_SERVER['HTTP_X_SESSION'];
        }

        $parts = array_merge($parts, [
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($content),
            "Connection: Close",
            "",
            isset($content) ? $content : '',
        ]);

        fwrite($fp, implode("\r\n", $parts));
        fclose($fp);

        return true;
    }
}
