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
        $success = [];
        $failure = [];

        foreach ($this->jobs as $job) {
            try {
                $success[$job] = $this->dispatch($job);
            } catch (Throwable $e) {
                $failure[$job] = $e->getMessage();
            }
        }


        return compact('failure', 'success');
    }
}
