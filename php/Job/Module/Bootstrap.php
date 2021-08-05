<?php

namespace Basis\Job\Module;

use Basis\Metric\StartTime;
use Basis\Toolkit;
use Basis\Metric\Registry;
use Psr\Log\LoggerInterface;
use Throwable;

class Bootstrap
{
    use Toolkit;

    public array $jobs = [
        'tarantool.migrate',
        'module.register',
        'module.recover',
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

        $this->get(Registry::class)->housekeeping();

        $this->get(StartTime::class)->update();
        $this->info('bootstrap complete');

        return compact('failure', 'success');
    }
}
