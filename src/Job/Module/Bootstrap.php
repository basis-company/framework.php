<?php

namespace Basis\Job\Module;

use Basis\Job;
use Exception;
use Basis\Filesystem;

class Bootstrap extends Job
{
    public function run(Filesystem $fs)
    {
        $cache = $fs->getPath('.cache');
        if (is_dir($cache)) {
            foreach ($fs->listFiles('.cache') as $file) {
                unlink($fs->getPath('.cache/'.$file));
            }
            rmdir($cache);
        }
        try {
            $this->dispatch('tarantool.migrate');
            $this->dispatch('tarantool.cache');
        } catch (Exception $e) {
            return [
                'migration' => $e->getMessage(),
            ];
        }

        $this->dispatch('module.defaults');
        $this->dispatch('module.register');
    }
}
