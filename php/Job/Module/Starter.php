<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
use Basis\Task;
use Job\Background;
use Symfony\Component\Process\Process;

class Starter
{
    private array $tasks = [];

    public function __construct(
        public readonly Dispatcher $dispatcher,
    ) {
    }

    public function run()
    {
        ini_set('max_execution_time', 0);

        if (!file_exists('var')) {
            mkdir('var');
        }
        chmod('var', 0777);

        if (!file_exists('var/telemetry')) {
            posix_mkfifo('var/telemetry', 0777);
        }
        chmod('var/telemetry', 0777);

        if (getenv('BASIS_ENVIRONMENT') !== 'dev') {
            exec('/var/www/html/composer.phar dump-autoload --classmap-authoritative > /dev/null');
        } else {
            exec('/var/www/html/composer.phar dump-autoload > /dev/null');
        }

        $this->register('module.process', [
            'job' => 'module.telemetry',
            'iterations' => 1024,
        ]);

        $this->register('module.bootstrap')->limit(1);

        $service = $this->dispatcher->getServiceName();
        if ($this->dispatcher->getClass("$service.background")) {
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
            if (array_key_exists('consumer', $handler) && $handler['consumer']) {
                $params = array_merge($params, $handler['consumer']);
            }
            foreach (range(1, $max) as $_) {
                $this->register('nats.consume', $params);
            }
        }

        proc_open('apache2-foreground', [STDIN, STDOUT, STDOUT], $pipes);

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
            if (file_exists('var/restart')) {
                unlink('var/restart');
                foreach ($this->tasks as $task) {
                    $task->stop();
                }
            }
            foreach ($this->tasks as $task) {
                $task->finalize();
            }
            foreach ($this->tasks as $task) {
                $task->start();
            }
            sleep(1);
        }
    }
}
