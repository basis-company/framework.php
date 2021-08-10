<?php

namespace Basis\Job\Module;

use Basis\Executor;
use Basis\Job;
use Basis\Metric\JobQueueLength;
use Tarantool\Mapper\Plugin\Spy;

class Execute extends Job
{
    public function run(Executor $executor)
    {
        $this->get(JobQueueLength::class)->update();
        $this->getMapper()->getPlugin(Spy::class)->reset();

        $recover = $this->dispatch('module.recover');

        if ($recover->new + $recover->recovered) {
            $this->info('recover', get_object_vars($recover));
        }

        $executor->process();
    }
}
