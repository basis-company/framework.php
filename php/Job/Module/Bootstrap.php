<?php

namespace Basis\Job\Module;

use Basis\Toolkit;
use Throwable;

class Bootstrap
{
    use Toolkit;

    public array $jobs = [
        'tarantool.migrate',
        'module.register',
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


        return compact('failure', 'success');
    }
}
