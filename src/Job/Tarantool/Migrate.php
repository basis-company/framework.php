<?php

namespace Basis\Job\Tarantool;

use Basis\Filesystem;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;

class Migrate
{
    public function run(Mapper $mapper, Bootstrap $bootstrap, Filesystem $fs)
    {
        $mapper->getPlugin(Annotation::class)->migrate();

        foreach ($fs->listFiles('resources/migrations') as $path) {
            list($ym, $filename) = explode('/', $path);
            $namespace = date_create_from_format('Ym', $ym)->format('FY');

            list($date, $time, $name) = explode('_', substr($filename, 0, -4));
            $class = $namespace.'\\'.$name;

            if (!class_exists($class, false)) {
                include $fs->getPath('resources/migrations/'.$path);
            }
            $bootstrap->register($class);
        }

        $bootstrap->migrate();
    }
}
