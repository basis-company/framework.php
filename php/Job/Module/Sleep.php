<?php

namespace Basis\Job\Module;

use Basis\Job;

class Sleep extends Job
{
    public float $seconds = 1;

    public function run()
    {
        if (!$this->seconds) {
            return;
        }

        return usleep($this->seconds * 1000000);
    }
}
