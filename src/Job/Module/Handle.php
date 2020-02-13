<?php

namespace Basis\Job\Module;

use Basis\Application;
use Basis\Context;
use Basis\Dispatcher;
use Basis\Event;
use Basis\Job;
use Basis\Service;
use Exception;

class Handle extends Job
{
    public $event;
    public $eventId;
    public $context;

    public $sync = false;

    public function run(Application $app, Dispatcher $dispatcher, Event $event, Service $service)
    {
        $start = microtime(1);
        $subscription = $event->getSubscription();

        $patterns = [];
        foreach (array_keys($subscription) as $pattern) {
            if ($service->eventMatch($this->event, $pattern)) {
                $patterns[] = $pattern;
            }
        }

        if (!count($patterns)) {
            $existingSubscription = $this->find('event.subscription', [
                'service' => $service->getName(),
            ]);
            foreach ($existingSubscription as $candidate) {
                $nick = $candidate->getType()->nick;
                if (!array_key_exists($nick, $subscription)) {
                    $this->dispatch('event.unsubscribe', [
                        'event' => $nick,
                        'service' => $service->getName(),
                    ]);
                }
            }
            return $dispatcher->send('event.feedback', [
                'eventId' => $this->eventId,
                'service' => $service->getName(),
                'result' => [
                    'message' => 'no subscription'
                ],
            ]);
        }

        $this->get(Context::class)->event = $this->eventId;

        $parts = explode('.', $this->event);
        $action = array_pop($parts);
        $space = implode('.', $parts);

        $listeners = [];
        foreach ($patterns as $pattern) {
            foreach ($subscription[$pattern] as $listener) {
                if (!array_key_exists($listener, $listeners)) {
                    $listeners[$listener] = $app->get('Listener\\'.$listener);
                    $listeners[$listener]->event = $this->event;
                    $listeners[$listener]->eventId = $this->eventId;
                    $listeners[$listener]->context = $this->context;
                    $listeners[$listener]->space = $space;
                    $listeners[$listener]->action = $action;
                }
            }
        }


        $data = [];
        $issues = [];
        foreach ($listeners as $nick => $listener) {
            try {
                $data[$nick] = $app->call([$listener, 'run']);
                $event->fireChanges($nick);
            } catch (Exception $e) {
                $issues[$nick] =  [
                    'message' => $e->getMessage(),
                    'trace' => explode(PHP_EOL, $e->getTraceAsString()),
                ];
            }
        }

        $result = [
            'data' => $data,
            'issues' => $issues,
            'time' => microtime(1) - $start,
        ];

        if ($this->sync) {
            return $result;
        }

        $dispatcher->send('event.feedback', [
            'eventId' => $this->eventId,
            'service' => $service->getName(),
            'result' => $result
        ]);
    }
}
