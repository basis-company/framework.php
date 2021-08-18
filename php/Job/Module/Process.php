<?php

namespace Basis\Job\Module;

use Basis\Configuration\Monolog;
use Basis\Configuration\Telemetry;
use Basis\Converter;
use Basis\Job;
use Basis\Telemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;

class Process extends Job
{
    public string $job;
    public ?object $params = null;
    public bool $logging = true;

    public int $iterations = 1;

    public function run(Converter $converter, Tracer $tracer)
    {
        $this->get(Monolog::class)->setName($this->job);
        $this->get(Telemetry::class)->setName($this->job);

        if (strpos($this->job, 'module.') === 0) {
            $this->get(Monolog::class)->setName(explode('.', $this->job)[1]);
        }

        while ($this->iterations--) {
            $result = null;
            $success = false;

            try {
                $params = $converter->toArray($this->params);
                $result = $this->dispatch($this->job, $params);
                $success = true;

                if ($result && $this->logging) {
                    if (!is_object($result) || count(get_object_vars($result))) {
                        $this->info($this->job . ' success', $result);
                    }
                }

                $this->dispatch('module.changes', [ 'producer' => $this->job ]);
                $this->dispatch('module.flush');
            } catch (Throwable $e) {
                $this->exception($e);
            }

            if ($this->iterations) {
                $this->dispatch('module.sleep');
                $tracer->reset();
            }
        }
    }
}
