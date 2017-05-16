<?php

namespace Basis;

use LogicException;
use League\Container\Container;
use ReflectionClass;
use ReflectionProperty;

class Runner
{
    private $app;
    private $mapping;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getMapping()
    {
        if(!$this->mapping) {

            $classes = array_merge(
                $this->app->get(Filesystem::class)->listClasses('Jobs'),
                $this->app->get(Framework::class)->listClasses('Jobs')
            );

            $jobs = [];
            foreach($classes as $class) {
                list($name, $group) = array_map('lcfirst', array_reverse(explode("\\", $class)));
                $nick = "$group.$name";

                if(!array_key_exists($nick, $jobs)) {
                    $jobs[strtolower($nick)] = $class;
                }
            }

            $this->mapping = $jobs;
        }

        return $this->mapping;
    }

    public function getJobClass($nick)
    {
        if(!strstr($nick, '.')) {
            throw new LogicException("Incorrect nick - $nick");
        }

        $mapping = $this->getMapping();
        if(!array_key_exists($nick, $mapping)) {
            throw new LogicException("No job $nick");
        }

        $class = $mapping[$nick];

        if(!class_exists($class)) {
            throw new LogicException("No class for job $nick");
        }
        return $class;
    }

    public function dispatch($nick, $arguments = [])
    {
        $class = $this->getJobClass($nick);

        $instance = $this->app->get($class);
        if(array_key_exists(0, $arguments)) {
            $arguments = $this->castArguments($class, $arguments);
        }

        foreach($arguments as $k => $v) {
            $instance->$k = $v;
        }

        $container = $this->app->get(Container::class);
        return $container->call([$instance, 'run']);
    }

    private function castArguments($class, $arguments)
    {
        $reflection = new ReflectionClass($class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        if(count($properties) == 1) {
            return [
                $properties[0]->getName() => count($arguments) == 1
                    ? $arguments[0]
                    : implode(' ', $arguments)
            ];
        }
        return $arguments;
    }
}
