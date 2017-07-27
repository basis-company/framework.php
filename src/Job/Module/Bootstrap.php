<?php

namespace Basis\Job\Module;

use Basis\Filesystem;
use Basis\Job;
use Exception;

class Bootstrap extends Job
{
    public function run(Filesystem $fs)
    {
        $result = [];
        $cache = $fs->getPath('.cache');
        if (is_dir($cache)) {
            foreach ($fs->listFiles('.cache') as $file) {
                unlink($fs->getPath('.cache/'.$file));
            }
            rmdir($cache);
        }

        $jobs = ['tarantool.migrate', 'tarantool.cache', 'module.defaults', 'module.register'];
        foreach ($jobs as $job) {
            try {
                $result[$job] = $this->dispatch($job);
            } catch (Exception $e) {
                $result[$job] = $e->getMessage();
            }
        }

        return $result;
    }
}
