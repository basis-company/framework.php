<?php

namespace Basis\Job\Module;

use Basis\Filesystem;
use Basis\Job;

class Version extends Job
{
    public function run(Filesystem $fs)
    {
        $version = [
            'service' => null,
            'php' => PHP_VERSION,
        ];

        if (file_exists($fs->getPath('version.php'))) {
            $git = include $fs->getPath('version.php');
            $version['service'] = $git['tag'] ?: $git['short_sha'];
        }

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
