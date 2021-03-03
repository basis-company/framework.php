<?php

namespace Basis\Job\Module;

use Basis\Job;
use Swoole\Coroutine;

class Sleep extends Job
{
    public bool $coroutine = false;
    public float $seconds = 1;

    public function run()
    {
        if (!$this->seconds) {
            return;
        }

        if ($this->coroutine && class_exists(Coroutine::class) && Coroutine::getContext() !== null) {
            return Coroutine::sleep($this->seconds);
        }

        return usleep($this->seconds * 1000000);
    }
}
