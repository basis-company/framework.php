<?php

namespace Basis\Job\Module;

use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;

class Telemetry
{
    private float $dumpInterval = 0.5;
    private int $spanCountLimit = 64;
    private string $pipePath = 'var/telemetry';
    private bool $disableTracing = false;

    private $pipe;

    public function __construct(
        private LoggerInterface $logger,
        private PrometheusExporter $prometheusExporter,
        private Registry $registry,
        private ZipkinExporter $zipkinExporter,
        private ZipkinTransport $zipkinTransport,
    ) {
        $this->dumpInterval = floatval(getenv('TELEMETRY_DUMP_INTERVAL')) ?: $this->dumpInterval;
        $this->pipePath = getenv('TELEMETRY_PIPE_PATH') ?: $this->pipePath;
        $this->spanCountLimit = intval(getenv('TELEMETRY_SPAN_COUNT_LIMIT')) ?: $this->spanCountLimit;

        if (getenv('TELEMETRY_TRACING_DISABLE')) {
            $this->disableTracing = getenv('TELEMETRY_TRACING_DISABLE') == 'true';
        }
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

        if ($buffer === false || feof($this->pipe)) {
            fclose($this->pipe);
            $this->pipe = null;
        }

        if (trim($buffer) === 'PING') {
            return [];
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
        if ($this->disableTracing) {
            // drop spans
            return [];
        }

        if (count($spans)) {
            $chunks = array_chunk($spans, $this->spanCountLimit);
            foreach ($chunks as $i => $chunk) {
                $data = array_map([$this->zipkinExporter, 'convertSpan'], $chunk);
                if ($this->zipkinTransport->write($data)) {
                    unset($chunks[$i]);
                } else {
                    $spans = array_merge(...$chunks);
                    $this->logger->info('span write failure', [
                        'buffer' => count($spans),
                        'data' => $data,
                    ]);

                    // reset client
                    $this->zipkinTransport->setClient(new CurlHttpClient());

                    // try again
                    return $spans;
                }
            }

            // all chunks were exported
            return [];
        }

        return $spans;
    }
}
