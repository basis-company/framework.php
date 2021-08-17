<?php

namespace Basis\Job\Module;

use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use Psr\Log\LoggerInterface;
use SplFileObject;

class Telemetry
{
    public string $source = 'var/telemetry';

    public function __construct(
        private LoggerInterface $logger,
        private PrometheusExporter $prometheusExporter,
        private Registry $registry,
        private ZipkinExporter $zipkinExporter,
        private ZipkinTransport $zipkinTransport,
    ) {
    }

    public function run()
    {
        $activity = null;
        $spans = [];
        $telemetryInterval = floatval(getenv('TELEMETRY_DUMP_INTERVAL') ?: 0.5);

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
            if (!$activity || ($activity + $telemetryInterval) < microtime(true)) {
                $this->renderMetrics($this->registry);
                if ($this->exportTraces($spans)) {
                    $spans = [];
                }
                $activity = microtime(true);
            }
        }
    }

    private function getInstances(): array
    {
        $telemetry = fopen('var/telemetry', 'r');
        $buffer = fgets($telemetry, 8192);
        fclose($telemetry);

        if ($buffer) {
            $instances = unserialize($buffer);
            if ($instances == false) {
                $this->logger->info('invalid buffer', [
                    'buffer' => $buffer,
                ]);
            }
            return is_array($instances) ? $instances : [ $instances ];
        }
        return [];
    }

    private function renderMetrics(Registry $registry): void
    {
        $registry->set('uptime', time() - $registry->get('start_time'));

        if ($registry->get('background_start')) {
            $registry->set('background_hold', time() - $registry->get('background_start'));
        }

        $this->prometheusExporter->toFile('public/metrics', 'svc_');
    }

    private function exportTraces(array $spans): bool
    {
        if (!count($spans)) {
            return false;
        }

        $data = [];
        foreach ($spans as $span) {
            $data[] = $this->zipkinExporter->convertSpan($span);
        }

        return $this->zipkinTransport->write($data);
    }
}
