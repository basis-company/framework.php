<?php

namespace Basis;

use LogicException;
use League\Container\Container;

class Runner
{
    private $app;

    function __construct(Application $app)
    {
        $this->app = $app;
    }

    function listJobs()
    {
        $classes = array_merge(
            $this->app->get(Filesystem::class)->listClasses('Jobs'),
            $this->app->get(Framework::class)->listClasses('Jobs')
        );

        $jobs = [];
        foreach($classes as $class) {
            list($name, $group) = array_map('strtolower', array_reverse(explode("\\", $class)));
            $nick = "$group.$name";

            if(!array_key_exists($nick, $jobs)) {
                $jobs[$nick] = $class;
            }
        }

        return $jobs;
    }

    function dispatch($nick, $arguments = [])
    {
        if(!strstr($nick, '.')) {
            throw new LogicException("Incorrect nick - $nick");
        }

        list($group, $name) = array_map('ucfirst', explode('.', $nick));

        $className = "Jobs\\$group\\$name";

        $class = $this->app->get(Filesystem::class)->completeClassName($className);

        if(!class_exists($class)) {
            $frameworkClass = $this->app->get(Framework::class)->completeClassName($className);
            if(class_exists($frameworkClass)) {
                $class = $frameworkClass;
            }
        }

        if(!class_exists($class)) {
            throw new LogicException("No job $nick");
        }

        $instance = $this->app->get($class);
        foreach($arguments as $k => $v) {
            $instance->$k = $v;
        }

        $container = $this->app->get(Container::class);
        return $container->call([$instance, 'run']);
    }
}
