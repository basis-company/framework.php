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
                $spans = $this->processSpans($spans);
                $this->renderMetrics($this->registry);
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

            $packages = [
                (object) ['spans' => [], 'traces' => []],
                (object) ['spans' => [], 'traces' => []],
            ];

            foreach ($spans as $span) {
                $traceId = $span->getSpanContext()->getTraceId();
                foreach ($packages as $package) {
                    if (!array_key_exists($traceId, $package->traces)) {
                        if (count($package->traces) < $this->traceCountLimit) {
                            $package->traces[$traceId] = $traceId;
                        }
                    }
                    if (array_key_exists($traceId, $package->traces)) {
                        $package->spans[] = $span;
                        break;
                    }
                }
            }

            $data = array_map([$this->zipkinExporter, 'convertSpan'], $packages[0]->spans);

            if ($this->zipkinTransport->write($data)) {
                return count($packages) > 1 ? $packages[1]->spans : [];
            }

            if (count($packages) == 1) {
                return $packages[0]->spans;
            }

            return array_merge($packages[0]->spans, $packages[1]->spans);
        }

        return $spans;
    }
}
