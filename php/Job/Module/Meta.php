<?php

namespace Basis\Job\Module;

use Basis\Application;
use Basis\Dispatcher;
use Basis\Http;

class Meta
{
    public function run(Application $app, Http $http, Dispatcher $dispatcher)
    {
        $jobs = [];
        foreach ($dispatcher->getJobs() as $job => $class) {
            if (strpos($job, $app->getName() . '.') !== false) {
                $jobs[] = $job;
            }
        }
        return [
            'routes' => $http->getRoutes(),
            'jobs' => $jobs,
        ];
    }
}
