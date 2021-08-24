<?php

namespace Basis\Job\Module;

use Basis\Converter;
use Basis\Lock;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;

class Flush
{
    private float $spanDurationThreshold = 0.1;

    public function __construct(
        private Converter $converter,
        private Lock $lock,
        private LoggerInterface $logger,
        private Operations $operations,
        private Tracer $tracer,
    ) {
        $spanDurationThreshold = floatval(getenv('TELEMETRY_SPAN_DURATION_THRESHOLD'));
        $this->spanDurationThreshold = $spanDurationThreshold ?: $this->spanDurationThreshold;
    }

    public function run()
    {
        $this->converter->flushCache();
        $this->lock->releaseLocks();

        if (!file_exists('var/telemetry')) {
            return;
        }

        while (!$this->tracer->getActiveSpan()->getEnd()) {
            $this->tracer->getActiveSpan()->end();
        }

        $instances = [];

        if (count($this->tracer->getSpans())) {
            foreach ($this->tracer->getSpans() as $span) {
                if ($span->getDuration() >= $this->spanDurationThreshold) {
                    $instances[] = $span;
                }
            }
        }

        if ($this->operations->count()) {
            $instances[] = $this->operations;
        }

        if (count($instances)) {
            $telemetry = fopen('var/telemetry', 'w');
            while (!flock($telemetry, LOCK_EX)) {
                $this->logger->info('wait for telemetry lock');
                usleep(1000);
            }
            ob_start();
            $length = fwrite($telemetry, serialize($instances) . PHP_EOL);
            if (!$length) {
                $this->logger->error('telemetry dump failure', [
                    'output' => ob_get_clean(),
                ]);
            } else {
                ob_end_clean();
            }
            flock($telemetry, LOCK_UN);
            fclose($telemetry);
        }

        $this->tracer->reset();
        $this->operations->reset();
    }
}
