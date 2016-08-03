<?php

namespace Basis;

use LogicException;

class Runner
{
    private $app;
    private $jobs = [];

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
        list($group, $name) = array_map('ucfirst', explode('.', $nick));
        $config = $this->app->get(Config::class);

        $class = $config['app.namespace'];
        if($class) {
            $class .= '\\';
        }

        $class .= "Jobs\\$group\\$name";

        if(!class_exists($class)) {
            $frameworkClass = "Basis\\Jobs\\$group\\$name";
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

        return $instance->run();
    }
}
