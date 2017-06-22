<?php

namespace Basis\Job\Module;

use Basis\Job;
use Exception;

class Bootstrap extends Job
{
    public function run()
    {
        $this->dispatch('module.defaults');
        $this->dispatch('module.register');

        try {
            $this->dispatch('tarantool.migrate');
        } catch (Exception $e) {
            return [
                'migration' => $e->getMessage(),
            ];
        }
    }
}
