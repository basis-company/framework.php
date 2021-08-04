<?php

namespace Basis\Job\Module;

use Basis\Metric\BackgroundHold;
use Basis\Metric\JobQueueLength;
use Basis\Metric\Uptime;
use Basis\Toolkit;
use Tarantool\Mapper\Plugin\Spy;

class Tick
{
    use Toolkit;

    public function run()
    {
        $this->get(BackgroundHold::class)->update();
        $this->get(Uptime::class)->update();

        if (getenv('SERVICE_JOB_QUEUE_METRIC') !== 'false') {
            $this->get(JobQueueLength::class)->update();
        }

        $this->getMapper()->getPlugin(Spy::class)->reset();

        $this->dispatch('module.sleep');
    }
}
