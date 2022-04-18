<?php

namespace Basis\Job\Module;

use Basis\Configuration\Monolog;
use Basis\Configuration\Telemetry;
use Basis\Converter;
use Basis\Job;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;
use Throwable;

class Process extends Job
{
    public string $job;
    public ?object $params = null;
    public bool $logging = false;
    public bool $loggerSetup = true;

    public int $iterations = 1;

    public function run(Converter $converter, Tracer $tracer)
    {
        if ($this->loggerSetup) {
            $this->get(Client::class)->setName($this->job);
            $this->get(Monolog::class)->setName($this->job);
            $this->get(Telemetry::class)->setName($this->job);

            if (strpos($this->job, 'module.') === 0) {
                $thread = explode('.', $this->job)[1];
                $this->get(Monolog::class)->setName($thread);
                // nats connection name prefixed with service name
                $this->get(Client::class)->setName($this->app->getName() . '.' . $thread);
            }
        }

        $iterations = $this->iterations;
        $data = [];

        while ($iterations--) {
            $result = null;
            $success = false;

            try {
                $params = $converter->toArray($this->params);
                $start = microtime(true);
                $result = $this->dispatch($this->job, $params);
                $success = true;
                if ($this->logging) {
                    $params['time'] = number_format(microtime(true) - $start, 3);
                    $this->info($this->job, $params);
                }
            } catch (Throwable $e) {
                if ($this->logging) {
                    $this->exception($e);
                }
                $result = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }

            $this->dispatch('module.changes', ['producer' => $this->job]);
            $this->dispatch('module.flush');

            $data[] = compact('result', 'success');

            if ($iterations) {
                $this->dispatch('module.sleep');
                $this->dispatch('module.housekeeping');
                $tracer->reset();
            }
        }

        return $this->iterations == 1 ? $data[0] : ['success' => true, 'data' => $data];
    }
}
