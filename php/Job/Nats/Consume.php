<?php

namespace Basis\Job\Nats;

use Basis\Context;
use Basis\Dispatcher;
use Basis\Lock;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

class Consume
{
    public int $batch = 1;
    public int $debug = 0;
    public int $delay = 1;
    public int $expires = 30;
    public int $limit = 1024;
    public string $subject;

    public function __construct(
        public readonly Client $client,
        public readonly Context $context,
        public readonly Dispatcher $dispatcher,
        public readonly Lock $lock,
        public readonly LoggerInterface $logger,
        public readonly Tracer $tracer,
    ) {
    }

    public function run()
    {
        $bucket = $this->client
            ->getApi()
            ->getBucket('service_handlers');

        $json = $bucket->get('subject_' . $this->subject);

        if (!$json) {
            throw new Exception("No configuration for $this->subject");
        }

        $handler = json_decode($json);

        if (!$handler) {
            throw new Exception("Invalid configuration for $this->subject");
        }

        $consumer = $this->client
            ->setLogger($this->debug ? $this->logger : null)
            ->setName($this->subject . '.consume')
            ->getApi()
            ->getStream($this->dispatcher->getServiceName())
            ->getConsumer($this->subject);

        $consumer
            ->setBatching($this->batch)
            ->setDelay($this->delay)
            ->setExpires($this->expires)
            ->setIterations($this->limit)
            ->handle($this->handle(...));
    }

    public function handle($request)
    {
        return $this->context
            ->execute($request->context, function () use ($request) {
                $this->dispatcher->dispatch('module.process', [
                    'job' => $request->job,
                    'params' => $request->params,
                    'logging' => true,
                ]);
                $this->tracer->reset();
            });
    }
}
