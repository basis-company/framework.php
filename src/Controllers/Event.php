<?php

namespace Basis\Controllers;

use Basis\Application;
use Exception;
use Basis\Event as BasisEvent;

class Event
{
    public function index(Application $app, BasisEvent $event)
    {
        try {
            if (!array_key_exists('event', $_REQUEST)) {
                throw new Exception('No event defined');
            }

            if (!array_key_exists('context', $_REQUEST)) {
                throw new Exception('No context defined');
            }

            $context = json_decode($_REQUEST['context']);

            if (!$context) {
                throw new Exception('Invalid context');
            }

            $class = 'Listeners';
            foreach (explode('.', $_REQUEST['event']) as $part) {
                $class .= '\\'.ucfirst($part);
            }

            $instance = $app->get($class);
            foreach ($context as $k => $v) {
                $instance->$k = $v;
            }
            if (!method_exists($instance, 'run')) {
                throw new Exception('No run method for '.$class);
            }

            $result = $app->call([$instance, 'run']);

            $event->fireChanges();

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
