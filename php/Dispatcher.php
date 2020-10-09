<?php

namespace Basis;

use Basis\Converter;
use Basis\Feedback\Feedback;
use Exception;
use LogicException;
use OpenTelemetry\Tracing\Tracer;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Coroutine\Http\Client;
use Throwable;

class Dispatcher
{
    private Container $container;
    private ?array $jobs = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send(string $job, array $params = [], string $service = null): Client
    {
        $job = strtolower($job);
        if (!$service) {
            $service = $this->getJobService($job);
        }

        if ($service == $this->getServiceName()) {
            return $this->dispatch($job, $params, $service);
        }

        $host = $this->dispatch('resolve.address', [ 'name' => $service ])->host;
        $url = "/api/index/" . str_replace('.', '/', $job);

        $headers = [];
        if ($this->container->has(ServerRequestInterface::class)) {
            $request = $this->container->get(ServerRequestInterface::class);
            foreach ([ 'x-session', 'x-real-ip' ] as $header) {
                if ($request->hasHeader($header)) {
                    $headers[$header] = $request->getHeaderLine($header);
                }
            }
        }

        $context = $this->get(Context::class)->toArray();
        $span = $this->get(Tracer::class)->getActiveSpan()->getSpanContext();

        $form = [
            'rpc' => json_encode([
                'context' => $context,
                'job'     => $job,
                'params'  => $params,
                'span'    => [
                    'traceId' => $span->getTraceId(),
                    'spanId'  => $span->getSpanId(),
                ],
            ]),
        ];

        $client = new Client($host, 80);
        $client->setHeaders($headers);
        $client->post($url, $form);

        return $client;
    }

    public function dispatch(string $job, array $params = [], string $service = null): object
    {
        $job = strtolower($job);
        
        $converter = $this->get(Converter::class);

        if (!$service) {
            $service = $this->getJobService($job);
        }

        if ($service == $this->getServiceName()) {
            $class = $this->getClass($job);
            if (!$class) {
                throw new Exception("No class for job $job");
            }
            try {
                $instance = $this->container->create($class);
                foreach ($converter->toObject($params) as $k => $v) {
                    $instance->$k = $v;
                }
                $result = $this->call($instance, 'run');
            } catch (Feedback $feedback) {
                throw new Exception(json_encode($feedback->serialize()));
            } catch (Throwable $e) {
                throw $e;
            }
            return (object) $converter->toObject($result);
        }

        $body = null;
        $limit = getenv('BASIS_DISPATCHER_RETRY_COUNT') ?: 32;
        while (!$body && $limit-- > 0) {
            $body = $this->send($job, $params, $service)->body;
            if (!$body) {
                $this->dispatch('module.sleep', [ 'seconds' => 0.5 ]);
            }
        }

        if (!$body) {
            throw new Exception("Host $host ($service) is unreachable");
        }

        $result = json_decode($body);
        if (!$result || !$result->success) {
            $exception = new Exception($result->message ?: $body);
            if ($result->trace) {
                $exception->remoteService = $service;
                $exception->remoteTrace = $result->trace;
            }
            throw $exception;
        }

        return (object) $this->get(Converter::class)->toObject($result->data);
    }

    public function isLocalJob(string $job): bool
    {
        return $this->getJobService($job) == $this->getServiceName();
    }

    public function getClass(string $job): ?string
    {
        if (array_key_exists($job, $this->getJobs())) {
            return $this->getJobs()[$job];
        }
        return null;
    }

    public function getJobService(string $job): string
    {
        if (array_key_exists($job, $this->getJobs())) {
            return $this->getServiceName();
        }

        return explode('.', $job)[0];
    }

    public function getJobs(): array
    {
        if ($this->jobs === null) {
            $this->jobs = [];
            $registry = $this->get(Registry::class);
            $converter = $this->get(Converter::class);
            foreach ($registry->listClasses('job') as $class) {
                $start = strpos($class, 'Job\\') + 4;
                $xtype = $converter->classToXtype(substr($class, $start));
                if (array_key_exists($xtype, $this->jobs)) {
                    continue;
                }
                $this->jobs[$xtype] = $class;
                if ($this->getServiceName() && strpos($class, 'Basis\\') !== 0) {
                    $xtype = $this->getServiceName() . '.' . $xtype;
                    $this->jobs[$xtype] = $class;
                }
            }
        }

        return $this->jobs;
    }

    protected function getServiceName()
    {
        return $this->get(Application::class)->getName();
    }

    protected function get($class)
    {
        return $this->container->get(...func_get_args());
    }

    protected function call($class, $method)
    {
        return $this->container->call(...func_get_args());
    }
}
