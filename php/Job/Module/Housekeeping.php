<?php

namespace Basis\Job\Module;

use Basis\Container;
use Basis\Converter;
use Basis\Lock;

class Housekeeping
{
    public function run(Container $container)
    {
        if ($container->hasInstance(Lock::class)) {
            $container->get(Lock::class)->releaseLocks();
        }
        $container->get(Converter::class)->flushCache();
    }
}
