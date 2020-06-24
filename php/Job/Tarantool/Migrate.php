<?php

namespace Basis\Job\Tarantool;

use Basis\Toolkit;
use Basis\Registry;
use ReflectionClass;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\Procedure;

class Migrate
{
    use Toolkit;

    public function run(Bootstrap $bootstrap, Registry $registry)
    {
        $mapper = $this->get(Mapper::class);

        // entities
        $mapper->getPlugin(Annotation::class)->migrate();

        // migrations
        $migrations = [];
        foreach ($registry->listClasses('Migration') as $class) {
            $timestamp = $registry->getPropertyDefaultValue($class, 'created_at');
            if (!array_key_exists($timestamp, $migrations)) {
                $migrations[$timestamp] = [];
            }
            $migrations[$timestamp][] = $class;
        }
        ksort($migrations);
        foreach ($migrations as $collection) {
            foreach ($collection as $class) {
                if (method_exists($class, '__construct')) {
                    $class = $this->get($class);
                }
                $bootstrap->register($class);
            }
        }

        $bootstrap->migrate();

        // procedures
        foreach ($registry->listClasses('Procedure') as $class) {
            $mapper->getPlugin(Procedure::class)->register($class);
        }
    }
}
