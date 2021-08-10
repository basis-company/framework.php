<?php

namespace Basis\Job\Module;

use Basis\Metric\BackgroundHold;
use Basis\Metric\JobQueueLength;
use Basis\Toolkit;
use Tarantool\Mapper\Plugin\Spy;

class Tick
{
    use Toolkit;

    public int $iterations = 1024;

    public function run()
    {
        while ($this->iterations--) {
            if (getenv('SERVICE_JOB_QUEUE_METRIC') !== 'false') {
                $this->get(JobQueueLength::class)->update();
            }
            $this->getMapper()->getPlugin(Spy::class)->reset();
            $this->dispatch('module.sleep');
        }
    }
}
