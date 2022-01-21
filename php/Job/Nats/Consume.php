<?php

namespace Basis\Job\Nats;

use Basis\Context;
use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;

class Consume
{
    public string $stream;
    public int $limit = 1024;
    public int $batch = 1;

    public function __construct(
        public readonly Client $client,
        public readonly Context $context,
        public readonly Dispatcher $dispatcher,
        public readonly LoggerInterface $logger,
    ) {
    }

    public function run()
    {
        $this->client
            ->setLogger($this->logger)
            ->setName($this->stream . '.consume')
            ->getApi()
            ->getStream($this->stream)
            ->getConsumer($this->stream)
            ->setBatching($this->batch)
            ->setDelay(0)
            ->setExpires(30)
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
                $this->get(Tracer::class)->reset();
            });
    }
}
