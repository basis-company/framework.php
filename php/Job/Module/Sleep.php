<?php

namespace Basis\Job\Module;

use Amp\Delayed;
use Amp\Promise;
use Basis\Job;

class Sleep extends Job
{
    public float $seconds = 1;

    public function run()
    {
        if (!$this->seconds) {
            return;
        }

        if (class_exists(Delayed::class)) {
            $result = yield new Delayed($this->seconds * 1000);
            return;
        }

        return usleep($this->seconds * 1000000);
    }
}
