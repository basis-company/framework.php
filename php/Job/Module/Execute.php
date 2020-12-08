<?php

namespace Basis\Job\Module;

use Basis\Job;
use Basis\Executor;

class Execute extends Job
{
    public function run(Executor $executor)
    {
        $executor->process();
    }
}
