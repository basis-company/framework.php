<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Context;
use Basis\Event;
use Basis\Service;
use Basis\Toolkit;
use Exception;
use OpenTelemetry\Tracing\Tracer;
use OpenTelemetry\Transport;
use OpenTelemetry\Exporter;

class Api
{
    use Toolkit;

    public function __process()
    {
        return $this->index();
    }

    public function index()
    {
        if (!array_key_exists('rpc', $_REQUEST)) {
            return [
                'success' => false,
                'message' => 'No rpc defined',
            ];
        }
        $data = json_decode($_REQUEST['rpc']);

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format',
            ];
        }

        $tracer = $this->get(Tracer::class);
        if ($data->context) {
            $this->get(Context::class)->apply($data->context);
        }

        $request = is_array($data) ? $data : [$data];

        $response = [];
        foreach ($request as $rpc) {
            $result = $this->process($rpc);
            if (is_null($result)) {
                $result = [];
            }
            if (property_exists($rpc, 'tid')) {
                $result['tid'] = $rpc->tid;
            }
            $response[] = $result;
        }

        try {
            if ($this->get(Event::class)->hasChanges()) {
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
                $this->get(Event::class)->fireChanges($request[0]->job);
                $changesSpan->end();

                if ($last) {
                    $last->end();
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Fire changes failure: '.$e->getMessage()];
        }

        try {
            $response[0]['timing'] = microtime(true) - $tracer->getActiveSpan()->getStart();
            if ($this->get(Service::class)->getName() != 'audit') {
                if ($response[0]['timing'] >= 0.05) {
                    $this->get(Exporter::class)->flush($tracer, $this->get(Transport::class));
                }
            }
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
                'service' => $this->get(Service::class)->getName(),
                'trace' => explode(PHP_EOL, $e->getTraceAsString()),
            ];
            if (property_exists($e, 'remoteTrace')) {
                $error['remoteTrace'] = $e->remoteTrace;
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
