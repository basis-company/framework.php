<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Basis\Metric;
use Basis\Registry as ServiceRegistry;
use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Importer\PrometheusImporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\SpanContext;
use Basis\Telemetry\Tracing\Tracer;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;

class Telemetry
{
    private string $name = 'default';

    public function setName(string $name): self
    {
        $this->name = $name;

        if ($this->container->has(Tracer::class)) {
            $this->container->get(Tracer::class)
                ->getActiveSpan()
                ->setName($name);
        }

        return $this;
    }

    public function init(Container $container)
    {
        $this->container = $container;

        $container->share(ZipkinExporter::class, function () use ($container) {
            return new ZipkinExporter([ 'serviceName' => $container->get(Application::class)->getName() ]);
        });

        $container->share(ZipkinTransport::class, function () {
            $schema = getenv('TELEMETRY_ZIPKIN_SCHEMA') ?: 'http';
            $hostname = getenv('TELEMETRY_ZIPKIN_HOSTNAME') ?: 'audit-zipkin';
            $path = getenv('TELEMETRY_ZIPKIN_PATH') ?: '/api/v2/spans';
            $port = getenv('TELEMETRY_ZIPKIN_PORT') ?: 9411;
            return new ZipkinTransport(new CurlHttpClient(), $hostname, $port, $path, $schema);
        });

        $container->share(Registry::class, function () use ($container) {
            $registry = new Registry();

            if (file_exists('public/metrics')) {
                $importer = new PrometheusImporter($registry, $container->get(Info::class));
                $importer->fromFile('public/metrics', 'svc_');
            }

            return $registry;
        });

        $container->share(Operations::class, function () {
            return new Operations();
        });

        $container->share(PrometheusExporter::class, function () use ($container) {
            return new PrometheusExporter($container->get(Registry::class), $container->get(Info::class));
        });

        $container->share(Info::class, function () use ($container) {
            $info = new Info();
            foreach ($container->get(ServiceRegistry::class)->listClasses('metric') as $class) {
                $metric = $container->get($class);
                if ($metric instanceof Metric) {
                    $info->set($metric->getNick(), $metric->getHelp(), $metric->getType());
                }
            }
            return $info;
        });

        $container->share(Tracer::class, function () use ($container) {
            $tracer = null;

            if ($container->has(ServerRequestInterface::class)) {
                $request = $container->get(ServerRequestInterface::class);
                $body = $request->getParsedBody();
                if (is_array($body) && array_key_exists('rpc', $body)) {
                    $data = json_decode($body['rpc']);
                    if ($data !== null && property_exists($data, 'span')) {
                        $traceId = $data->span->traceId;
                        $spanId = $data->span->spanId;
                        $tracer = new Tracer(SpanContext::restore($traceId, $spanId));
                        if (property_exists($data->span, 'parentSpanId')) {
                            $parentSpanId = $data->span->parentSpanId;
                            $parentSpan = SpanContext::restore($traceId, $parentSpanId);
                            $tracer->getActiveSpan()->setParentSpanContext($parentSpan);
                        }
                    }
                }
            }
            if (!$tracer) {
                $tracer = new Tracer();
            }

            $tracer->getActiveSpan()->setName($this->name);

            return $tracer;
        });
    }
}
