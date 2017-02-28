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
        $jobs = array_keys($runner->listJobs(false));

        return compact('jobs');
    }
}