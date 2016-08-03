<?php

namespace Basis;

use LogicException;

class Http
{
    private $app;

    function __construct(Application $app)
    {
        $this->app = $app;
    }

    function process($uri)
    {
        list($controller, $method) = $this->getChain($uri);
        $className = "Controllers\\$controller";
        $class = $this->app->get(Filesystem::class)->completeClassName($className);
        if(!class_exists($class)) {
            $this->app->get(Framework::class)->completeClassName($className);
        }

        if(!method_exists($class, $method)) {
            return "$controller/$method not found";
        }

        $result = $this->app->get($class)->$method();

        if(is_array($result) || is_object($result)) {
            return json_encode($result);
        } else {
            return $result;
        }
    }

    function getChain($uri)
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
