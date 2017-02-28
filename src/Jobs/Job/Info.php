<?php

namespace Basis\Jobs\Job;

use Basis\Runner;
use ReflectionClass;

/**
 * Get Jobs information
 */
class Info
{
    public function run(Runner $runner)
    {
        $jobs = $runner->listJobs();

        $info = [];
        foreach($jobs as $nick => $class) {
            // get comment
            $reflection = new ReflectionClass($class);
            $comments = [];
            foreach(explode(PHP_EOL, $reflection->getDocComment()) as $line) {
                if(!in_array(substr($line, 0, 3), ['/**', ' */'])) {
                    $comments[] = trim(substr($line, 2));
                }
            }
            $comment = implode(PHP_EOL, $comments);
            if(!$comment) {
                $comment = '-';
            }
            $info[] = compact('nick', 'class', 'comment');
        }

        return compact('info');
    }
}