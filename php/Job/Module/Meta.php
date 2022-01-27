<?php

namespace Basis\Job\Module;

use Basis\Application;
use Basis\Dispatcher;
use Basis\Http;

class Meta
{
    public bool $filter = true;

    public function run(Application $app, Http $http, Dispatcher $dispatcher)
    {
        $jobs = [];

        foreach ($dispatcher->getJobs() as $job => $class) {
            if (!$this->filter || strpos($job, $app->getName() . '.') !== false) {
                $jobs[] = $job;
            }
        }

        return [
            'routes' => $http->getRoutes(),
            'jobs' => $jobs,
        ];
    }
}
