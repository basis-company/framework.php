<?php

namespace Basis;

use League\Container\Container;
use LogicException;

class Http
{
    private $app;

public     function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function process($uri)
    {
        list($controller, $method) = $this->getChain($uri);
        $className = "Controllers\\".ucfirst($controller);
        $class = $this->app->get(Filesystem::class)->completeClassName($className);
        if(!class_exists($class)) {
            $frameworkClass = $this->app->get(Framework::class)->completeClassName($className);
        }

        if(!class_exists($class)) {
            if(!class_exists($frameworkClass)) {
                throw new LogicException("No class for $controller $controller, [$class, $frameworkClass]");
            }
            $class = $frameworkClass;
        }

        if(!method_exists($class, $method)) {
            return "$controller/$method not found";
        }

        $container = $this->app->get(Container::class);
        $result = $container->call([$container->get($class), $method]);

        if(is_array($result) || is_object($result)) {
            return json_encode($result);
        } else {
            return $result;
        }
    }

    public function getChain($uri)
    {
        $chain = explode('/', $uri);
        foreach($chain as $k => $v) {
            if(!$v) {
                unset($chain[$k]);
            }
        }

        $chain = array_values($chain);

        if(!count($chain)) {
            $chain[] = 'index';
        }

        if(count($chain) == 1) {
            $chain[] = 'index';
        }

        return $chain;
    }
}
