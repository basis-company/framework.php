<?php

namespace Basis;

use LogicException;

class Runner
{
    private $app;
    private $jobs = [];

    function __construct(Application $app, Filesystem $fs)
    {
        $this->app = $app;
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
            throw new LogicException("No job $nick");
        }

        $instance = $this->app->get($class);
        foreach($arguments as $k => $v) {
            $instance->$k = $v;
        }

        return $instance->run();
    }
}
