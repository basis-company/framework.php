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
        public readonly LoggerInterface $logger,
        public readonly Tracer $tracer,
    ) {
    }

    public function run()
    {
        $this->client
            ->setLogger($this->debug ? $this->logger : null)
            ->setName($this->subject . '.consume')
            ->getApi()
            ->getStream($this->subject)
            ->getConsumer($this->subject)
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
