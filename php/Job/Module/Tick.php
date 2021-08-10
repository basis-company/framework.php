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

    public int $iterations = 1024;

    public function run()
    {
        while ($this->iterations--) {
            $this->get(BackgroundHold::class)->update();
            $this->get(Uptime::class)->update();

            if (getenv('SERVICE_JOB_QUEUE_METRIC') !== 'false') {
                $this->get(JobQueueLength::class)->update();
            }

            $recover = $this->dispatch('module.recover');

            if ($recover->new + $recover->recovered) {
                $this->info('recover', get_object_vars($recover));
            }

            $this->getMapper()->getPlugin(Spy::class)->reset();

            $this->dispatch('module.sleep');
        }
    }
}
