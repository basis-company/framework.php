<?php

namespace Basis;

use Exception;
use Ramsey\Uuid\Uuid;

class Service
{
    private $app;
    private $queue;
    private $tube;

    private $task;
    private $session;

    public function __construct(Application $app, Queue $queue, $tube)
    {
        $this->app = $app;
        $this->queue = $queue;
        $this->tube = $tube;

        $this->queue->init($tube);

        if($this->queue->has('router')) {
            $this->queue->put('router', [
                'uuid' => Uuid::uuid4()->toString(),
                'job' => 'router.register',
                'data' => [
                    'tube' => $tube,
                    'jobs' => $app->dispatch('service.getJobs')['jobs'],
                ]
            ]);
        }
    }

    public function send($nick, $arguments, $opts = [])
    {
        $task = [
            'uuid' => Uuid::uuid4()->toString(),
            'tube' => $this->tube,
            'job' => 'router.process',
            'data' => [
                'job' => $nick,
                'data' => $arguments,
            ]
        ];

        foreach($opts as $k => $v) {
            if(!array_key_exists($k, $task)) {
                $task[$k] = $v;
            }
        }

        $this->queue->put('router', $task);
    }

    public function process($timeout = 30)
    {
        $task = $this->queue->take($this->tube, $timeout);

        if(!$task) {
            return;
        }

        $this->task = $task;

        $params = $task->offsetExists('data') ? $task['data'] : [];
        if($task->offsetExists('session')) {
            $this->session = $task['session'];
        }

        try {
            $data = $this->app->dispatch($task['job'], $params);
            if($task->offsetExists('tube')) {
                $this->queue->put($task['tube'], [
                    'uuid' => $task['uuid'],
                    'data' => $data
                ]);
            }

        } catch(Exception $e) {
            if($task->offsetExists('tube')) {
                $this->queue->put($task['tube'], [
                    'uuid' => $task['uuid'],
                    'data' => $e->getMessage(),
                    'error' => true,
                ]);
            }
        }

        $this->task = null;
        $this->session = null;

        $task->ack();

        return $task;
    }

    public function getTask()
    {
        return $this->task;
    }

    public function getTube()
    {
        return $this->tube;
    }

    public function getSession()
    {
        return $this->session;
    }
}