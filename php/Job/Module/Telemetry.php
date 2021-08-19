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
    private $pipe;

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
        $this->pipe = fopen('var/telemetry', 'r');

        $dumpInterval = floatval(getenv('TELEMETRY_DUMP_INTERVAL') ?: 0.5);

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

                if (!$activity || ($activity + $dumpInterval) < microtime(true)) {
                    $activity = microtime(true);
                    $this->renderMetrics($this->registry);
                    if ($this->exportTraces($spans)) {
                        $spans = [];
                    }
                }
            }
        }

        fclose($this->pipe);
    }

    private function getInstances(): array
    {
        $buffer = fgets($this->pipe, 131072);

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
