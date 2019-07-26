<?php

namespace Basis\Job\Module;

use Basis\Job;

class Runner extends Job
{
    public function run()
    {
        // job name to process
        $job = getenv('BASIS_JOB');
        if (!$job) {
            throw new Exception("BASIS_JOB is not defined");
        }

        // loops count limit
        $loops = +getenv('BASIS_LOOPS') ?: (getenv('BASIS_ENVIRONMENT') == 'dev' ? 1 : 100);
        $current = 1;

        // delay between calls in milliseconds
        $delay = +getenv('BASIS_DELAY') ?: 0;

        while ($loops--) {
            echo '#', $current++," ", $job, PHP_EOL;
            ob_flush();

            $start = microtime(true);
            $result = $this->dispatch($job);
            echo "time: ", microtime(true) - $start, PHP_EOL;

            echo json_encode($result, JSON_PRETTY_PRINT), PHP_EOL;
            usleep($delay * 1000);
        }
    }}
