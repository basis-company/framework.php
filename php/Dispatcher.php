<?php

namespace Basis;

use Basis\Feedback\Feedback;
use Basis\Nats\Client;
use Basis\Nats\Message\Payload;
use Basis\Telemetry\Tracing\Tracer;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Throwable;

class Dispatcher
{
    private ?array $jobs = null;
    private ?array $handlers = null;
    private Container $container;
    private CurlHttpClient $client;

    private string $token = '';

    public function __construct(Container $container)
    {
        $this->client = new CurlHttpClient();
        $this->container = $container;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function httpTransport(string $job, array $params = [], string $service = null, $system = false)
    {
        $job = strtolower($job);
        if (!$service) {
            $service = $this->getJobService($job);
        }

        if ($service == $this->getServiceName()) {
            return $this->dispatch($job, $params, $service);
        }

        $host = $this->dispatch('resolve.address', [ 'name' => $service ])->host;
        $url = '/' . str_replace('.', '/', $job);

        $postRequest = false;
        $query = [];
        foreach ($params as $k => $v) {
            if ($v || is_object($v) || is_array($v)) {
                $postRequest = true;
                break;
            }

            $query[] = "$k=$v";
        }

        $body = null;
        if ($postRequest) {
            $body = json_encode($params);
        } else {
            $url .= '?' . implode('&', $query);
        }

        $span = $this->get(Tracer::class)->getActiveSpan()->getSpanContext();

        $headers = [
            'authorization' => 'Bearer ' . $this->getToken($system),
            'x-span-id' => $span->getSpanId(),
            'x-trace-id' => $span->getTraceId(),
        ];

        if ($this->container->has(ServerRequestInterface::class)) {
            $request = $this->container->get(ServerRequestInterface::class);
            foreach (['x-session', 'x-real-ip', 'x-channel'] as $header) {
                if ($request->hasHeader($header)) {
                    $headers[$header] = $request->getHeaderLine($header);
                }
            }
        }

        $response = $this->client->request($postRequest ? 'POST' : 'GET', 'http://' . $host . $url, [
            'headers' => $headers,
            'body' => $body,
            'timeout' => 600,
        ]);

        $content = $response->getContent(false);

        if ($response->getStatusCode() >= 300) {
            throw new Exception($content);
        }

        return $content;
    }

    public function flush(string $job, array $params = [], string $service = null)
    {
        return $this->get(Cache::class)->delete(func_get_args());
    }

    public function getToken($system = false): string
    {
        if ($system) {
            return $this->get(Application::class)->getToken();
        }

        if ($this->token) {
            return $this->token;
        }

        if ($this->container->has(ServerRequestInterface::class)) {
            $request = $this->container->get(ServerRequestInterface::class);
            if ($request->hasHeader('authorization')) {
                $authorization = $request->getHeaderLine('authorization');
                return explode(' ', $authorization)[1];
            }
        }

        // service token is default
        return $this->getToken(true);
    }

    public function system(string $job, array $params = [], string $service = null): object
    {
        return $this->dispatch($job, $params, $service, true);
    }

    public function dispatch(string $job, array $params = [], string $service = null, $system = false): object
    {
        return $this->get(Cache::class)->wrap(func_get_args(), function () use ($job, $params, $service, $system) {
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
                    $span = $this->get(Tracer::class)->createSpan($job);
                    foreach ($params as $k => $v) {
                        if (is_object($v) || is_array($v)) {
                            continue;
                        }
                        if (stripos($k, 'password') !== false) {
                            $v = str_repeat('*', strlen($v));
                        }
                        $span->setAttribute($k, $v);
                    }
                    $instance = $this->container->create($class);
                    foreach ($converter->toObject($params) as $k => $v) {
                        $instance->$k = $v;
                    }
                    $result = $this->call($instance, 'run') ?: [];
                } catch (Feedback $feedback) {
                    $span->end();
                    throw new Exception(json_encode($feedback->serialize()));
                } catch (Throwable $e) {
                    $span->end();
                    throw $e;
                }
                $span->end();

                if ($result instanceof ResponseInterface) {
                    // return result as is
                    return $result;
                }

                if (is_array($result) && array_key_exists('expire', $result)) {
                    // force entity convertion to array with app cleanup
                    $result = $converter->toArray($result, true);
                }

                return (object) $converter->toObject($result);
            }

            $body = null;
            $limit = getenv('BASIS_DISPATCHER_RETRY_COUNT') ?: 16;

            while (!$body && $limit-- > 0) {
                try {
                    $body = $this->httpTransport($job, $params, $service, $system);
                } catch (Exception $e) {
                    if ($e->getMessage() != 'Expired token') {
                        throw $e;
                    }
                    $this->get(LoggerInterface::class)->error($e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                if (!$body) {
                    $this->get(LoggerInterface::class)->info('retry', [
                        'service' => $service,
                        'job' => $job,
                        'params' => $params,
                        'system' => $system,
                        'sleep' => 1,
                    ]);
                    $this->dispatch('module.sleep', [ 'seconds' => 1 ]);
                }
            }

            if (!$body) {
                $host = $this->dispatch('resolve.address', [ 'name' => $service ])->host;
                throw new Exception("Host $host ($service) is unreachable");
            }

            $result = (object) json_decode($body);
            if (!$result || property_exists($result, 'success') && !$result->success) {
                if (!$result) {
                    throw new Exception("Invalid result from $service: $body");
                }
                $message = property_exists($result, 'message') && $result->message ? $result->message : $body;
                $exception = new Exception($message);
                if (property_exists($result, 'trace') && $result->trace) {
                    $exception->remoteService = $service;
                    $exception->remoteTrace = $result->trace;
                }
                throw $exception;
            }

            return (object) $this->get(Converter::class)->toObject($result);
        });
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

    public function getHandlers(): array
    {
        if ($this->handlers == null) {
            $this->handlers = [];
            $converter = $this->get(Converter::class);
            foreach ($this->getJobs() as $nick => $class) {
                if (method_exists($class, 'getHandlers') && getenv('BASIS_ENVIRONMENT') !== 'testing') {
                    $handlers = $this->call($class, 'getHandlers');
                    if (!$converter->isTuple($handlers)) {
                        $handlers = [$handlers];
                    }
                    foreach ($handlers as $handler) {
                        if (!is_array($handler)) {
                            throw new Exception("Invalid handler configuration for $nick");
                        }
                        $default = [
                            'threads' => 1,
                            'subject' => str_replace('.', '_', $nick),
                            'job' => $nick,
                        ];
                        $this->handlers[] = array_merge($default, $handler);
                    }
                }
            }
            $this->handlers[] = [
                'subject' => $this->getServiceName(),
                'threads' => intval(getenv('DEFAULT_CONSUMER_COUNT') ?: 1),
            ];
        }

        return $this->handlers;
    }

    public function getJobService(string $job): string
    {
        $key = 'getJobService-' . $job;
        return $this->get(Cache::class)->wrap($key, function () use ($job) {
            if (array_key_exists($job, $this->getJobs())) {
                return $this->getServiceName();
            }

            return explode('.', $job)[0];
        });
    }

    public function getJobs(): array
    {
        if ($this->jobs === null) {
            $this->jobs = [];
            $handlers = [];
            $registry = $this->get(Registry::class);
            $converter = $this->get(Converter::class);
            foreach ($registry->listClasses('job') as $class) {
                $start = strpos($class, 'Job\\') + 4;
                $nick = $converter->classToXtype(substr($class, $start));
                if ($this->getServiceName() && strpos($class, 'Basis\\') !== 0) {
                    // add service prefix when job is not override
                    if (!array_key_exists($nick, $this->jobs)) {
                        $nick = $this->getServiceName() . '.' . $nick;
                    }
                }
                $this->jobs[$nick] = $class;
            }
        }

        return $this->jobs;
    }

    public function getServiceName()
    {
        return $this->get(Application::class)->getName();
    }

    public function send(string $job, array $params = [], string $service = null): void
    {
        $service = $service ?: $this->getJobService($job);

        $natsMessageId = null;
        // normalize params so job_queue will not failure on assist
        if (array_key_exists('nats-message-id', $params)) {
            $natsMessageId = $params['nats-message-id'];
            unset($params['nats-message-id']);
        }

        try {
            $subject = $this->dispatch('nats.subject', compact('job', 'service', 'params'))->subject;
            if (!$subject) {
                throw new Exception("No subject for $job");
            }
            $payload = [
                'job' => $job,
                'params' => $this->get(Converter::class)->toArray($params),
                'context' => $this->get(Context::class)->toArray(),
            ];
            if ($natsMessageId) {
                $payload = new Payload(json_encode($payload), ['Nats-Msg-Id' => $natsMessageId]);
            }
            $this->get(Client::class)
                ->publish($subject, $payload);
        } catch (Throwable $e) {
            $this->get(LoggerInterface::class)->error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->get(Executor::class)->send($job, $params, $service);
        }
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
