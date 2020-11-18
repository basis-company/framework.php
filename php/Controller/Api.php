<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Context;
use Basis\Event;
use Basis\Toolkit;
use Exception;
use OpenTelemetry\Tracing\Tracer;
use OpenTelemetry\Transport;
use OpenTelemetry\Exporter;
use Psr\Http\Message\ServerRequestInterface;

class Api
{
    use Toolkit;

    public function __process(ServerRequestInterface $request, Context $context)
    {
        return $this->index($request, $context);
    }

    public function index(ServerRequestInterface $request, Context $context)
    {
        $body = $request->getParsedBody();

        if ($body === null || !is_array($body)) {
            return [
                'success' => false,
                'message' => 'Invalid request',
            ];
        }

        if (!array_key_exists('rpc', $body)) {
            return [
                'success' => false,
                'message' => 'No rpc defined',
            ];
        }

        $data = json_decode($body['rpc']);

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format',
            ];
        }

        $tracer = $this->get(Tracer::class);

        $context->reset();

        if ($request->getHeaderLine('x-real-ip')) {
            $context->apply([
                'ip' => $request->getHeaderLine('x-real-ip'),
            ]);
        }

        if (property_exists($data, 'context') && $data->context) {
            $context->apply($data->context);
        }

        $request = is_array($data) ? $data : [$data];

        $response = [];
        foreach ($request as $rpc) {
            $result = $this->process($rpc);
            if ($result == null) {
                $result = [];
            }
            if (property_exists($rpc, 'tid')) {
                $result['tid'] = $rpc->tid;
            }
            $response[] = $result;
        }

        try {
            $event = $this->get(Event::class);
            if ($event->hasChanges()) {
                $last = null;
                $active = $tracer->getActiveSpan();
                foreach ($tracer->getSpans() as $candidate) {
                    if ($candidate->getParentSpanContext() == $active->getSpanContext()) {
                        $last = $candidate;
                        break;
                    }
                }
                if ($last) {
                    $last->setInterval($last->getStart(), 0);
                    $tracer->setActive($last);
                }

                $changesSpan = $tracer->createSpan('event.changes');
                $event->fireChanges($request[0]->job);
                $changesSpan->end();

                if ($last) {
                    $last->end();
                }
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fire changes failure: ' . $e->getMessage(),
                'trace' => explode(PHP_EOL, $e->getTraceAsString()),
            ];
        }

        try {
            $response[0]['time'] = round(microtime(true) - $tracer->getActiveSpan()->getStart(), 3);
            if ($this->app->getName() != 'audit') {
                if ($response[0]['time'] >= 0.1) {
                    $exporter = $this->get(Exporter::class);
                    $transport = $this->get(Transport::class);
                    $exporter->flush($tracer, $transport);
                }
            }
            $response[0]['timing'] = $response[0]['time'];
        } catch (Exception $e) {
            // no traces is not a problem
        }

        return is_array($data) ? $response : $response[0];
    }

    private function process($rpc)
    {
        if (!property_exists($rpc, 'job')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no job',
            ];
        }

        if (!property_exists($rpc, 'params')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no params',
            ];
        }

        try {
            $params = is_object($rpc->params) ? get_object_vars($rpc->params) : [];
            $data = $this->dispatch(strtolower($rpc->job), $params);
            $data = $this->removeSystemObjects($data);

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (Exception $e) {
            $error = [
                'success' => false,
                'message' => $e->getMessage(),
                'service' => $this->app->getName(),
                'trace' => explode(PHP_EOL, $e->getTraceAsString()),
            ];
            if (property_exists($e, 'remoteTrace')) {
                $error['remoteTrace'] = $e->remoteTrace;
                $error['remoteService'] = $e->remoteService;
            }
            return $error;
        }
    }

    private function removeSystemObjects($data)
    {
        if (!$data) {
            return [];
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                if ($v instanceof Application) {
                    unset($data[$k]);
                } else {
                    $data[$k] = $this->removeSystemObjects($v);
                }
            }
        }

        return $data;
    }
}
