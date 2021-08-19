<?php

namespace Basis\Job\Module;

use Basis\Converter;
use Basis\Lock;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;

class Flush
{
    public function __construct(
        private Converter $converter,
        private Lock $lock,
        private LoggerInterface $logger,
        private Operations $operations,
        private Tracer $tracer,
    ) {
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
            $spanDurationThreshold = floatval(getenv('TELEMETRY_SPAN_DURATION_THRESHOLD') ?: '0.1');
            foreach ($this->tracer->getSpans() as $span) {
                if ($span->getDuration() >= $spanDurationThreshold) {
                    $instances[] = $span;
                }
            }
        }

        if ($this->operations->count()) {
            $instances[] = $this->operations;
        }

        if (count($instances)) {
            $telemetry = fopen('var/telemetry', 'w');
            ob_start();
            $length = fwrite($telemetry, serialize($instances) . PHP_EOL);
            if (!$length) {
                $this->logger->error('telemetry dump failure', [
                    'output' => ob_get_clean(),
                ]);
            }
            ob_end_clean();
            fclose($telemetry);
        }

        $this->tracer->reset();
        $this->operations->reset();
    }
}
