<?php

namespace Basis\Controllers;

use Basis\Event;
use Basis\Filesystem;
use Basis\Runner;
use Exception;
use Tarantool\Mapper\Plugins\Spy;

class Api
{
    public function index(Runner $runner, Event $event, Spy $spy)
    {
        if(!array_key_exists('rpc', $_REQUEST)) {
            return [
                'success' => false,
                'message' => 'No rpc defined',
            ];
        }
        $data = json_decode($_REQUEST['rpc']);

        if(!$data) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format',
            ];
        }

        $request = is_array($data) ? $data : [$data];

        $response = [];
        foreach($request as $rpc) {
            $result = $this->process($runner, $rpc);
            if(property_exists($rpc, 'tid')) {
                $result['tid'] = $rpc->tid;
            }
            $response[] = $result;
        }

        $event->fireChanges($spy);

        return is_array($data) ? $response : $response[0];
    }

    private function process($runner, $rpc)
    {
        if(!property_exists($rpc, 'job')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no job',
            ];
        }

        if(!property_exists($rpc, 'params')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no params',
            ];
        }

        try {
            $params = is_object($rpc->params) ? get_object_vars($rpc->params) : [];
            return [
                'success' => true,
                'data' => $runner->dispatch($rpc->job, $params),
            ];

        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => explode(PHP_EOL, $e->getTraceAsString()),
            ];
        }
    }
}
