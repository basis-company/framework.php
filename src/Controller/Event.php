<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Event as BasisEvent;
use Basis\Service;
use Exception;

class Event
{
    public function index(Application $app, BasisEvent $event, Service $service)
    {
        try {
            $context = $this->getRequestContext();
            $subscription = $event->getSubscription();

            $patterns = [];
            foreach (array_keys($subscription) as $pattern) {
                if ($service->eventMatch($context->event, $pattern)) {
                    $patterns[] = $pattern;
                }
            }

            if (!count($patterns)) {
                $service->unsubscribe($context->event);
                throw new Exception("No subscription on event ".$context->event);
            }

            $listeners = [];
            foreach ($patterns as $pattern) {
                foreach ($subscription[$pattern] as $listener) {
                    if (!array_key_exists($listener, $listeners)) {
                        $listeners[$listener] = $app->get('Listener\\'.$listener);
                    }
                }
            }

            $result = [];
            $issues = [];
            foreach ($listeners as $nick => $listener) {
                $result[$nick] = $this->handleEvent($app, $listener, $context);
                try {
                    $event->fireChanges($nick);
                } catch (Exception $e) {
                    $issues[$nick] =  $e->getMessage();
                }
            }


            return [
                'success' => true,
                'data' => $result,
                'issues' => $issues,
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getRequestContext()
    {
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

        $context->event = $_REQUEST['event'];

        return $context;
    }

    private function handleEvent($app, $instance, $context)
    {
        foreach ($context as $k => $v) {
            $instance->$k = $v;
        }
        if (!method_exists($instance, 'run')) {
            throw new Exception('No run method for '.$class);
        }

        return $app->call([$instance, 'run']);
    }
}
