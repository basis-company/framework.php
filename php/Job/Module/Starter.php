<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Task;
use Job\Background;

class Starter
{
    private array $tasks = [];

    public function __construct(
        public readonly Dispatcher $dispatcher,
    ) {
    }

    public function run()
    {
        $this->register('module.process', [
            'job' => 'module.telemetry',
            'iterations' => 1024,
        ]);

        $this->register('module.bootstrap')->limit(1);

        if (class_exists(Background::class)) {
            $this->register('module.process', [
                'job' => 'module.background',
                'iterations' => 1024,
            ]);
        }

        if (getenv('SERVICE_EXECUTOR') !== 'false') {
            $this->register('module.process', [
                'job' => 'module.execute',
                'iterations' => 1024,
            ]);
        }

        foreach ($this->dispatcher->getHandlers() as $handler) {
            $max = 1;
            $params = ['subject' => $handler['subject']];
            if (array_key_exists('threads', $handler) && $handler['threads']) {
                $max = $handler['threads'];
            }
            if (array_key_exists('params', $handler) && $handler['params']) {
                $params = array_merge($params, $handler['params']);
            }
            foreach (range(1, $max) as $_) {
                $this->register('nats.consume', $params);
            }
        }

        $this->loop();
    }

    private function register(string $job, array $params = []): Task
    {
        $this->tasks[] = $task = new Task();
        return $task->job($job)->params($params);
    }

    private function loop(): void
    {
        while (true) {
            foreach ($this->tasks as $task) {
                $task->start();
            }
            foreach ($this->tasks as $task) {
                $task->finalize();
            }
            sleep(1);
        }
    }
}
