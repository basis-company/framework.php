<?php

namespace Basis\Job\Module;

class Version
{
    public function run()
    {
        $version = [
            'service' => null,
            'php' => PHP_VERSION,
        ];

        if (file_exists('version.php')) {
            $git = include 'version.php';
            $version['service'] = $git['tag'] ?: $git['short_sha'];
        }

        if (is_file('composer.lock')) {
            $info = json_decode(file_get_contents('composer.lock'));
            foreach ($info->packages as $package) {
                $version[$package->name] = $package->version;
            }
        }

        return compact('version');
    }
}
