<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Job;
use OpenTelemetry\Exporter;
use OpenTelemetry\Tracing\Tracer;
use OpenTelemetry\Transport;
use Psr\Log\LoggerInterface;

class Trace extends Job
{
    /**
     * minimum span duration in seconds
     */
    public ?float $threshold = null;

    public function run(LoggerInterface $logger)
    {
        if ($this->threshold === null) {
            $this->threshold = +getenv('SERVICE_TRACE_THRESHOLD') ?: 0.1
        }
        $blacklist = [
            'audit',
            'developer',
        ];
        if (in_array($this->app->getName(), $blacklist)) {
            return [
                'msg' => 'disable audition',
            ];
        }

        $exporter = $this->get(Exporter::class);
        $tracer = $this->get(Tracer::class);
        if (!$tracer->getActiveSpan()->getEnd()) {
            $tracer->getActiveSpan()->end();
        }

        $data = [];
        foreach ($tracer->getSpans() as $span) {
            $duration = ($span->getEnd() ?: $span->getStart()) - $span->getStart();
            if ($duration < $this->threshold) {
                continue;
            }
            $data[] = $exporter->convertSpan($span);
        }

        if (count($data)) {
            $this->send('audit.span.register', compact('data'));
        }
    }
}
