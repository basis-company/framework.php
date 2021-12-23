<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
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
        $this->register('module.bootstrap')->limit(1);

        $this->register('module.process', [
            'job' => 'module.telemetry',
            'iterations' => 1024,
        ]);

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

        foreach ($this->dispatcher->dispatch('nats.streams')->streams as $info) {
            $task = $this->register('nats.consume', ['stream' => $info->name]);
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
