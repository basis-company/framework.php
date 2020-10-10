<?php

namespace Basis\Job\Module;

use Basis\Job;
use Swoole\Coroutine;

class Sleep extends Job
{
    public float $seconds = 1;

    public function run()
    {
        if (!$this->seconds) {
            return;
        }

        if (class_exists(Coroutine::class) && Coroutine::getContext() !== null) {
            return Coroutine::sleep($this->seconds);
        }

        return usleep($this->seconds * 1000000);
    }
}
