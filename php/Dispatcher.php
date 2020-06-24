<?php

namespace Basis;

use Basis\Converter;
use Basis\Feedback\Feedback;
use Exception;
use GuzzleHttp\Client;
use LogicException;
use Throwable;

class Dispatcher
{
    private Container $container;
    private ?array $jobs = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
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
                foreach ($params as $k => $v) {
                    $instance->$k = $v;
                }
                $result = $this->call($instance, 'run');
            } catch (Feedback $feedback) {
                throw new Exception(json_encode($feedback->serialize()));
            } catch (Throwable $e) {
                throw new Exception("Error processing $job: " . $e->getMessage(), 0, $e);
            }
            return (object) $converter->toObject($result);
        }

        return $this->send($job, $params, $service)->wait();
    }

    public function send(string $job, array $params = [], string $service = null)
    {
        if (!$service) {
            $service = $this->getJobService($job);
        }

        $host = $this->dispatch('resolve.address', [ 'name' => $service ])->host;
        $url = "http://$host/api/index/" . str_replace('.', '/', $job);
        $context = $this->get(Context::class)->toArray();

        if ($this->container->has(Client::class)) {
            $client = $this->container->get(Client::class);
        } else {
            $client = $this->container->create(Client::class);
        }
        $response = $client->postAsync($url, [
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
    
            return (object) $this->get(Converter::class)->toObject($result->data);
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
