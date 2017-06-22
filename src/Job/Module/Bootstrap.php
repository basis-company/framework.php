<?php

namespace Basis\Job\Module;

use Basis\Runner;
use Exception;

class Bootstrap
{
    public function run(Runner $runner)
    {
        $runner->dispatch('module.defaults');
        $runner->dispatch('module.register');

        try {
            $runner->dispatch('tarantool.migrate');

        } catch (Exception $e) {
            return [
                'migration' => $e->getMessage();
            ];
        }
    }
}
