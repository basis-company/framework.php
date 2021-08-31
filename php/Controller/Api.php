<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Configuration\Telemetry;
use Basis\Context;
use Basis\Event;
use Basis\Http;
use Basis\Telemetry\Tracing\Tracer;
use Basis\Toolkit;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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

        // recreate container with rpc context
        $span = $this->getContainer()
            ->drop(Tracer::class)
            ->get(Telemetry::class)
            ->setName('http');

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

        $this->dispatch('module.changes', [ 'producer' => $request[0]->job ]);

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
            $start = microtime(true);
            $params = is_object($rpc->params) ? get_object_vars($rpc->params) : [];
            $data = $this->dispatch(strtolower($rpc->job), $params);
            $data = $this->removeSystemObjects($data);

            $context = [
                'time' => round(microtime(true) - $start, 3),
            ];

            foreach ($params as $k => $v) {
                if (is_string($v) || is_numeric($v)) {
                    $context[$k] = $v;
                }
            }

            $this->get(LoggerInterface::class)->info($rpc->job, $context);
            $this->get(Http::class)->setLogging(false);

            return [
                'success' => true,
                'data' => $data,
                'timing' => +number_format(microtime(true) - $start, 3),
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
                if (property_exists($e, 'remoteService')) {
                    $error['remoteService'] = $e->remoteService;
                }
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
