<?php

namespace Basis\Job\Nats;

use Basis\Configuration\Monolog;
use Basis\Dispatcher;
use Basis\Job;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

class Consume extends Job
{
    public int $batch = 1;
    public int $debug = 0;
    public int $delay = 0;
    public int $expires = 30;
    public int $limit = 1024;
    public string $subject;
    public bool $housekeeping = true;

    public function run()
    {
        $this->get(Monolog::class)->setName('nats.consume');

        $logger = null;
        if ($this->debug) {
            $logger = $this->get(LoggerInterface::class);
        }

        $this->get(Client::class)
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
        try {
            if (!$request->context->access) {
                if ($this->app->getName() == 'queue') {
                    // empty context
                    $this->actAs(1);
                }
                $this->error('invalid request context', get_object_vars($request));
                throw new Exception("No access defined");
            }
            $this->actAs($request->context->access);

            $processing = $this->dispatch('module.process', [
                'job' => $request->job,
                'params' => $request->params,
                'logging' => true,
                'loggerSetup' => false,
            ]);

            if (!$processing->success) {
                $this->schedule($request);
            }

            if ($this->housekeeping) {
                $this->dispatch('module.housekeeping');
            }

            $this->get(Tracer::class)->reset();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return $this->schedule($request);
        }
    }

    private function schedule($request)
    {
        if (is_object($request->params)) {
            $request->params = (array) $request->params;
        }
        if (explode('.', $request->job)[0] == $this->get(Dispatcher::class)->getServiceName()) {
            // domain jobs can be replayed via queue
            $params = $request->params;
            if ($request->job != 'queue.put') {
                $params = [
                    'job' => $request->job,
                    'params' => $request->params,
                ];
            }
            $params['key'] = microtime(true);
            $this->send('queue.put', $params);
        } else {
            // system jobs should be replayed as is
            $this->send($request->job, $request->params);
        }
    }
}
