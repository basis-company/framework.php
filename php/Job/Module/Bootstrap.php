<?php

namespace Basis\Job\Module;

use Basis\Metric\StartTime;
use Basis\Toolkit;
use Psr\Log\LoggerInterface;
use Throwable;

class Bootstrap
{
    use Toolkit;

    public array $jobs = [
        'data.migrate',
        'module.recover',
        'module.register',
        'tarantool.migrate',
    ];

    public function run()
    {
        $jobs = $this->jobs;

        if (class_exists('Job\\Bootstrap')) {
            $bootstrap = $this->app->getName() . '.bootstrap';
            if (!in_array($bootstrap, $jobs)) {
                $jobs[] = $bootstrap;
            }
        }

        $success = [];
        $failure = [];

        foreach ($jobs as $job) {
            try {
                $success[$job] = $this->dispatch($job);
            } catch (Throwable $e) {
                $failure[$job] = $e->getMessage();
            }
        }

        if (!$this->get(StartTime::class)->getValue()) {
            $this->get(StartTime::class)->update();
        }

        $this->info('bootstrap complete');

        $this->dispatch('module.flush');

        return compact('failure', 'success');
    }
}
