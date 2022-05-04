<?php

namespace Basis\Job\Module;

use Basis\Lock;
use Basis\Container;

class Housekeeping
{
    public bool $schema = false;

    public function run(Container $container)
    {
        if ($container->hasInstance(Lock::class)) {
            $container->get(Lock::class)->releaseLocks();
        }
    }
}
