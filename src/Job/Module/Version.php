<?php

namespace Basis\Job\Module;

use Basis\Filesystem;
use Basis\Job;

class Version extends Job
{
    public function run(Filesystem $fs)
    {
        $version = [
            'php' => PHP_VERSION
        ];

        $lock = $fs->getPath('composer.lock');
        if (is_file($lock)) {
            $info = json_decode(file_get_contents($lock));
            foreach ($info->packages as $package) {
                $version[$package->name] = $package->version;
            }
        }

        return compact('version');
    }
}
