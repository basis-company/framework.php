<?php

namespace Basis\Job\Module;

use Basis\Application;
use Basis\Context;
use Basis\Dispatcher;
use Basis\Event;
use Basis\Job;
use Exception;

class Handle extends Job
{
    public $context;
    public $event;
    public $eventId;

    public bool $feedback = false;

    public function run(Application $app, Dispatcher $dispatcher, Event $event)
    {
        $start = microtime(1);
        $subscription = $event->getSubscription();

        $patterns = [];
        foreach (array_keys($subscription) as $pattern) {
            if ($event->match($this->event, $pattern)) {
                $patterns[] = $pattern;
            }
        }

        if (!count($patterns)) {
            $existingSubscription = $this->find('event.subscription', [
                'service' => $app->getName(),
            ]);
            foreach ($existingSubscription as $candidate) {
                $nick = $candidate->getType()->nick;
                if (!array_key_exists($nick, $subscription)) {
                    $this->dispatch('event.unsubscribe', [
                        'event' => $nick,
                        'service' => $app->getName(),
                    ]);
                }
            }
            if ($this->feedback) {
                $this->send('event.feedback', [
                    'event' => $this->eventId,
                    'service' => $dispatcher->getServiceName(),
                    'result' => [
                        'msg' => 'no subscription',
                    ],
                ]);
            }
            return [
                'msg' => 'no subscription',
            ];
        }

        $this->get(Context::class)->event = $this->eventId;

        $parts = explode('.', $this->event);
        $action = array_pop($parts);
        $space = implode('.', $parts);

        $listeners = [];
        foreach ($patterns as $pattern) {
            foreach ($subscription[$pattern] as $listener) {
                if (!array_key_exists($listener, $listeners)) {
                    $listeners[$listener] = $app->get('Listener\\' . $listener);
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
                $data[$nick] = $app->call($listener, 'run');
                $event->fireChanges($nick);
            } catch (Exception $e) {
                $issues[$nick] = [
                    'message' => $e->getMessage(),
                    'trace' => explode(PHP_EOL, $e->getTraceAsString()),
                ];
            }
        }

        if ($this->feedback) {
            $this->send('event.feedback', [
                'event' => $this->eventId,
                'service' => $dispatcher->getServiceName(),
                'result' => [
                    'data' => $data,
                    'issues' => $issues,
                ]
            ]);
        }

        return [
            'data' => $data,
            'issues' => $issues,
            'time' => round(microtime(1) - $start, 3),
        ];
    }
}
