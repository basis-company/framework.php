<?php

namespace Basis\Job\Nats;

use Basis\Context;
use Basis\Dispatcher;
use Basis\Nats\Client;
use Psr\Log\LoggerInterface;

class Consume
{
    public string $stream;
    public int $limit = 1024;
    public int $batch = 8;

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
            ->setName($this->stream . ' consumer')
            ->getApi()
            ->getStream($this->stream)
            ->getConsumer($this->stream)
            ->setBatching($this->batch)
            ->setIterations($this->limit)
            ->handle($this->handle(...));
    }

    public function handle($request)
    {
        return $this->context
            ->execute($request->context, function () use ($request) {
                return $this->dispatcher->dispatch('module.process', [
                    'job' => $request->job,
                    'params' => $request->params,
                    'logging' => true,
                ]);
            });
    }
}
