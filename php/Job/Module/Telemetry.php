<?php

namespace Basis\Job\Module;

use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use Psr\Log\LoggerInterface;

class Telemetry
{
    private float $dumpInterval = 0.5;
    private int $traceCountLimit = 2;
    private string $pipePath = 'var/telemetry';

    private $pipe;

    public function __construct(
        private LoggerInterface $logger,
        private PrometheusExporter $prometheusExporter,
        private Registry $registry,
        private ZipkinExporter $zipkinExporter,
        private ZipkinTransport $zipkinTransport,
    ) {
        $this->dumpInterval = floatval(getenv('TELEMETRY_DUMP_INTERVAL')) ?: $this->dumpInterval;
        $this->pipePath = floatval(getenv('TELEMETRY_PIPE_PATH')) ?: $this->pipePath;
        $this->traceCountLimit = floatval(getenv('TELEMETRY_TRACE_COUNT_LIMIT')) ?: $this->traceCountLimit;
    }

    public function run()
    {
        $activity = null;
        $spans = [];

        while (true) {
            foreach ($this->getInstances() as $instance) {
                if (!is_object($instance)) {
                    $this->logger->info('Invalid telemetry instance', [
                        'instance' => $instance
                    ]);
                    continue;
                }

                match (get_class($instance)) {
                    Operations::class => $instance->apply($this->registry),
                    Span::class => $spans[] = $instance,
                    default => $this->logger->info('Invalid telemetry instance class', [
                        'class' => get_class($instance)
                    ]),
                };
            }

            if (!$activity || ($activity + $this->dumpInterval) < microtime(true)) {
                $activity = microtime(true);
                $this->renderMetrics($this->registry);
                $spans = $this->processSpans($spans);
            }
        }

        fclose($this->pipe);
    }

    private function getInstances(): array
    {
        if (!$this->pipe) {
            $this->initPipeline();
        }

        $buffer = fgets($this->pipe, 1048576);

        if ($buffer === false) {
            $this->initPipeline();
        }

        if ($buffer) {
            $instances = unserialize($buffer);
            if ($instances !== false) {
                return is_array($instances) ? $instances : [ $instances ];
            }
            $this->logger->info('invalid buffer', [
                'buffer' => $buffer,
            ]);
        }

        return [];
    }

    private function initPipeline()
    {
        if ($this->pipe) {
            fclose($this->pipe);
        }

        $this->pipe = fopen($this->pipePath, 'r');
    }

    private function renderMetrics(Registry $registry): void
    {
        $registry->set('uptime', time() - $registry->get('start_time'));

        if ($registry->get('background_start')) {
            $registry->set('background_hold', time() - $registry->get('background_start'));
        }

        $this->prometheusExporter->toFile('public/metrics', 'svc_');
    }

    private function processSpans(array $spans): array
    {
        if (count($spans)) {
            usort($spans, function ($a, $b) {
                return -1 * ($a->getDuration() <=> $b->getDuration());
            });

            // sorted parts
            $parts = [];
            foreach ($spans as $span) {
                $traceId = $span->getSpanContext()->getTraceId();
                foreach ([0, 1] as $index) {
                    if (!array_key_exists($index, $parts)) {
                        $parts[$index] = [];
                    }
                    if (!array_key_exists($traceId, $parts[$index])) {
                        if (count($parts[$index]) < $this->traceCountLimit) {
                            $parts[$index][$traceId] = [];
                        }
                    }
                    if (array_key_exists($traceId, $parts[$index])) {
                        $parts[$index][$traceId][] = $span;
                        break;
                    }
                }
            }

            $data = array_map([$this->zipkinExporter, 'convertSpan'], array_merge(...array_values($parts[0])));
            if ($this->zipkinTransport->write($data)) {
                return count($parts) > 1 ? array_merge(...array_values($parts[1])) : [];
            }

            return array_merge(...array_values(array_merge(...$parts)));
        }

        return $spans;
    }
}
