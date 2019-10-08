<?php

namespace Basis\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use OpenTelemetry\Exporter;
use OpenTelemetry\Exporter\BasisExporter;
use OpenTelemetry\Tracing\SpanContext;
use OpenTelemetry\Tracing\Tracer;
use OpenTelemetry\Transport;
use OpenTelemetry\Transport\TarantoolQueueTransport;

class OpenTelemetryProvider extends AbstractServiceProvider
{
    protected $provides = [
        BasisExporter::class,
        Exporter::class,
        TarantoolQueueTransport::class,
        Tracer::class,
        Transport::class,
    ];

    public function register()
    {
        $this->container->share(BasisExporter::class, function () {
            return new BasisExporter();
        });

        $this->container->share(Exporter::class, function () {
            return $this->getContainer()->get(BasisExporter::class);
        });

        $this->container->share(TarantoolQueueTransport::class, function () {
            return (new TarantoolQueueTransport())
                ->setQueue(
                    $this->getContainer()->getQueue('audit.tracing')
                );
        });

        $this->container->share(Tracer::class, function () {
            if (array_key_exists('rpc', $_REQUEST)) {
                $data = json_decode($_REQUEST['rpc']);
                if (property_exists($data, 'span')) {
                    $traceId = $data->span->traceId;
                    $spanId = $data->span->spanId;
                    $context = SpanContext::restore($traceId, $spanId);
                    return new Tracer($context);
                }
            }
            return new Tracer();
        });

        $this->container->share(Transport::class, function () {
            return $this->getContainer()->get(TarantoolQueueTransport::class);
        });
    }
}
