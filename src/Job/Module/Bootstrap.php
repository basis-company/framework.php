<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
use Basis\Event;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Runner;
use Basis\Service;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class Bootstrap
{
    public function run(Runner $runner)
    {
        $runner->dispatch('module.defaults');
        $runner->dispatch('module.register');
    }
}
