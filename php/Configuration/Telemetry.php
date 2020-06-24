<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Basis\Toolkit;
use OpenTelemetry\Exporter;
use OpenTelemetry\Exporter\BasisExporter;
use OpenTelemetry\Tracing\SpanContext;
use OpenTelemetry\Tracing\Tracer;
use OpenTelemetry\Transport;
use OpenTelemetry\Transport\TarantoolQueueTransport;
use Psr\Http\Message\ServerRequestInterface;

class Telemetry
{
    use Toolkit;

    public function init(Container $container)
    {
        $container->share(Exporter::class, BasisExporter::class);
        $container->share(Transport::class, TarantoolQueueTransport::class);

        $container->share(TarantoolQueueTransport::class, function () {
            $transport = new TarantoolQueueTransport();
            $queue = $this->getQueue('audit.tracing');
            return $transport->setQueue($queue);
        });

        $container->share(Tracer::class, function () use ($container) {

            $this->get(Application::class)
                ->registerFinalizer(function () use ($container) {
                    $container->drop(Tracer::class);
                });

            if ($container->has(ServerRequestInterface::class)) {
                $request = $container->get(ServerRequestInterface::class);
                $body = $request->getParsedBody();
                if (array_key_exists('rpc', $body)) {
                    $data = json_decode($body['rpc']);
                    if ($data && property_exists($data, 'span')) {
                        $traceId = $data->span->traceId;
                        $spanId = $data->span->spanId;
                        $context = SpanContext::restore($traceId, $spanId);
                        return new Tracer($context);
                    }
                }
            }

            return new Tracer();
        });
    }
}
