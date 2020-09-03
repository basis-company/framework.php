<?php

namespace Basis\Job\Module;

use Basis\Job;
use Swoole\Coroutine;

class Sleep extends Job
{
    public float $seconds = 1;

    public function run()
    {
        if (class_exists(Coroutine::class)) {
            return Coroutine::sleep($this->seconds);
        }

        return sleep($this->seconds);
    }
}
