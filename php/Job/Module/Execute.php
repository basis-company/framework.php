<?php

namespace Basis\Job\Module;

use Basis\Executor;
use Basis\Job;
use Basis\Metric\JobQueueLength;

class Execute extends Job
{
    public function run(Executor $executor)
    {
        $recover = $this->dispatch('module.recover');

        if ($recover->new + $recover->recovered) {
            $this->info('recover', get_object_vars($recover));
        }

        $executor->process();
    }
}
