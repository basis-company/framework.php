<?php

namespace Basis\Job\Module;

use Basis\Runner;

class Bootstrap
{
    public function run(Runner $runner)
    {
        $runner->dispatch('module.defaults');
        $runner->dispatch('module.register');
    }
}
