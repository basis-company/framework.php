<?php

namespace Basis\Job\Nats;

use Basis\Configuration\Monolog;
use Basis\Container;
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
    public int $delay = 0;
    public int $expires = 30;
    public int $limit = PHP_INT_MAX;
    public string $subject;
    public bool $housekeeping = true;

    public readonly LoggerInterface $logger;

    public function __construct(
        public readonly Container $container,
        public readonly Monolog $monolog,
        public readonly Client $client,
        public readonly Context $context,
        public readonly Dispatcher $dispatcher,
        public readonly Tracer $tracer,
    ) {
    }

    public function run()
    {
        $this->monolog->setName('nats.consume');

        $logger = null;
        if ($this->debug) {
            $logger = $this->container->get(LoggerInterface::class);
        }

        $this->client
            ->setLogger($logger)
            ->setName($this->subject)
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
                $processing = $this->dispatcher->dispatch('module.process', [
                    'job' => $request->job,
                    'params' => $request->params,
                    'logging' => true,
                    'loggerSetup' => false,
                ]);

                if (!$processing->success) {
                    if (explode('.', $request->job)[0] == $this->dispatcher->getServiceName()) {
                        // domain jobs can be replayed via queue
                        $this->dispatcher->send('queue.put', [
                            'job' => $request->job,
                            'params' => $request->params,
                            'key' => microtime(true),
                        ]);
                    } else {
                        // system jobs should be replayed as is
                        $this->dispatcher->send($request->job, $request->params);
                    }
                }

                if ($this->housekeeping) {
                    $this->dispatcher->dispatch('module.housekeeping');
                }

                $this->tracer->reset();
            });
    }
}
