<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Toolkit;
use Basis\Event as BasisEvent;
use Exception;
use Throwable;

class Event
{
    use Toolkit;

    public function __process()
    {
        return $this->call($this, 'index');
    }

    public function index(Application $app, BasisEvent $event)
    {
        $start = microtime(1);

        try {
            $info = $this->getEventInfo();
            $subscription = $event->getSubscription();

            $patterns = [];
            foreach (array_keys($subscription) as $pattern) {
                if ($event->match($info->event, $pattern)) {
                    $patterns[] = $pattern;
                }
            }

            if (!count($patterns)) {
                $event->unsubscribe($info->event);
                throw new Exception("No subscription on event " . $info->event);
            }

            $listeners = [];
            foreach ($patterns as $pattern) {
                foreach ($subscription[$pattern] as $listener) {
                    if (!array_key_exists($listener, $listeners)) {
                        $listeners[$listener] = $app->get('Listener\\' . $listener);
                    }
                }
            }
            
            $result = [];
            $issues = [];
            foreach ($listeners as $nick => $listener) {
                $result[$nick] = $this->handleEvent($app, $listener, $info);
                try {
                    $event->fireChanges($nick);
                } catch (Throwable $e) {
                    $issues[$nick] = $e->getMessage();
                }
            }

            return [
                'success' => true,
                'data' => $result,
                'issues' => $issues,
                'time' => microtime(1) - $start,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getEventInfo()
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

        $parts = explode('.', $_REQUEST['event']);
        $action = array_pop($parts);

        return (object) [
            'event' => $_REQUEST['event'],
            'space' => implode('.', $parts),
            'action' => $action,
            'context' => $context,
        ];
    }

    private function handleEvent($app, $instance, $info)
    {
        foreach ($info as $k => $v) {
            $instance->$k = $v;
        }
        if (!method_exists($instance, 'run')) {
            throw new Exception('No run method for ' . $class);
        }

        return $this->call($instance, 'run');
    }
}
