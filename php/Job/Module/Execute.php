<?php

namespace Basis\Job\Module;

use Basis\Executor;
use Basis\Job;
use Basis\Metric\JobQueueLength;

class Execute extends Job
{
    public function run(Executor $executor)
    {
        $executor->process();
    }
}
