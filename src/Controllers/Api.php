<?php

namespace Basis\Controllers;

use Basis\Filesystem;
use Basis\Runner;
use Exception;

class Api
{
    public function index(Runner $runner)
    {
        try {

            if(!array_key_exists('rpc', $_REQUEST)) {
                throw new Exception("No rpc defined");
            }
            $data = json_decode($_REQUEST['rpc']);
            if(!$data) {
                throw new Exception("Invalid rpc format");
            }

            if(!property_exists($data, 'job') || !property_exists($data, 'params')) {
                throw new Exception("Invalid rpc format");
            }

            $params = is_object($data->params) ? get_object_vars($data->params) : [];

            return [
                'success' => true,
                'data' => $runner->dispatch($data->job, $params),
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
