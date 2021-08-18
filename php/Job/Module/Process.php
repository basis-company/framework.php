<?php

namespace Basis\Job\Module;

use Basis\Configuration\Monolog;
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

    public function run(Converter $converter)
    {
        $this->get(Monolog::class)->name = $this->job;

        if (strpos($this->job, 'module.') === 0) {
            $this->get(Monolog::class)->name = explode('.', $this->job)[1];
        }

        while ($this->iterations--) {
            $result = null;
            $success = false;

            try {
                // new tracer each iteration
                $tracer = new Tracer();
                $tracer->getActiveSpan()->setName($this->app->getName() . '.' . $this->job);
                $this->getContainer()->share(Tracer::class, $tracer);

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
            }
        }
    }
}
