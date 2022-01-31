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
    public string $subject;
    public int $limit = 1024;
    public int $batch = 1;
    public int $debug = 0;

    private ?string $key = null;
    private int $refreshed = 0;

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

        if (property_exists($handler, 'threads')) {
            foreach (range(1, $handler->threads) as $i) {
                $candidate = 'consumer_' . $i . '_' . $this->subject;
                if ($this->lock->lock($candidate, 300)) {
                    // 1 minute timeout lock for consumer thread
                    $this->key = $candidate;
                }
            }

            if (!$this->key) {
                return;
                return $this->dispatcher
                    ->dispatch('module.sleep', ['seconds' => 60]);
            }
        }

        $this->client
            ->setLogger($this->debug ? $this->logger : null)
            ->setName($this->subject . '.consume')
            ->getApi()
            ->getStream($this->dispatcher->getServiceName())
            ->getConsumer($this->subject)
            ->setBatching($this->batch)
            ->setDelay(0)
            ->setExpires(30)
            ->setIterations($this->limit)
            ->handle($this->handle(...), $this->actualize(...));

        if ($this->key) {
            $this->lock->unlock($this->key);
        }
    }

    public function actualize($exit = true)
    {
        if (!$this->key) {
            return true;
        }

        if ($this->refreshed + 15 > time()) {
            return true;
        }

        try {
            $this->lock->refresh($this->key);
            $this->refreshed = time();
        } catch (Throwable $e) {
            $this->logger->info($e->getMessage(), [
                'pid' => getmypid(),
            ]);

            if ($exit) {
                throw $e;
            }

            $this->client
                ->getApi()
                ->getStream($this->dispatcher->getServiceName())
                ->getConsumer($this->subject)
                ->interrupt();
        }
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
                $this->actualize(false);
            });
    }
}
