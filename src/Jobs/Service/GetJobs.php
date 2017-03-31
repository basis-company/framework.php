<?php

namespace Basis\Jobs\Service;

use Basis\Runner;
use ReflectionClass;

/**
 * Get Jobs information
 */
class GetJobs
{
    public function run(Runner $runner)
    {
        $jobs = [];
        foreach($runner->getMapping() as $job => $class) {
            if(strpos($class, 'Basis') !== 0) {
                $jobs[] = $job;
            }
        }

        return compact('jobs');
    }
}