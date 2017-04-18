<?php

namespace Basis\Jobs\Tarantool;

use Basis\Filesystem;
use Tarantool\Mapper\Bootstrap;

class Migrate
{
    public function run(Bootstrap $bootstrap, Filesystem $fs)
    {
        foreach($fs->listFiles('resources/migrations') as $path) {
            list($ym, $filename) = explode('/', $path);
            $namespace = date_create_from_format('Ym', $ym)->format('FY');
            $class = $namespace.'\\'.substr($filename, 0, -4);
            if(!class_exists($class, false)) {
                include $fs->getPath('resources/migrations/'.$path);
            }
            $bootstrap->register($class);
        }

        $bootstrap->migrate();
    }
}