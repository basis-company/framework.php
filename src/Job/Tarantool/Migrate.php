<?php

namespace Basis\Job\Tarantool;

use Basis\Application;
use Basis\Filesystem;
use Basis\Job;
use ReflectionClass;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\Sequence;

class Migrate extends Job
{
    public function run(Mapper $mapper, Bootstrap $bootstrap, Filesystem $fs, Application $app)
    {
        $mapper->getPlugin(Annotation::class)->migrate();

        $migrations = [];
        foreach ($fs->listClasses('Migration') as $class) {
            $reflection = new ReflectionClass($class);
            $created_at = $reflection->getDefaultProperties()['created_at'];
            if (!array_key_exists($created_at, $migrations)) {
                $migrations[$created_at] = [];
            }
            $migrations[$created_at][] = $class;
        }
        ksort($migrations);

        foreach ($migrations as $collection) {
            foreach ($collection as $class) {
                if (method_exists($class, '__construct')) {
                    $class = $app->get($class);
                }
                $bootstrap->register($class);
            }
        }

        $bootstrap->migrate();

        $filename = $fs->getPath('.cache/mapper-meta.php');
        if (file_exists($filename)) {
            $this->dispatch('tarantool.cache');
        }
    }
}
