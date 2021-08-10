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

        $frameworkPath = 'vendor/basis-company/framework/composer.json';
        if (file_exists($frameworkPath)) {
            $framework = json_decode(file_get_contents($frameworkPath));
            if (is_object($framework) && property_exists($framework, 'version')) {
                $version[$framework->name] = $framework->version;
                if (strpos($framework->version, ':') !== false) {
                    $version[$framework->name] = explode(':', $framework->version)[1]
                }
            }
        }

        return compact('version');
    }
}
