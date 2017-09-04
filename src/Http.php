<?php

namespace Basis;

use League\Container\Container;
use LogicException;

class Http
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function process(string $uri): ?string
    {
        list($controller, $method) = $this->getChain($uri);
        $className = "Controller\\".ucfirst($controller);
        $class = $this->app->get(Filesystem::class)->completeClassName($className);
        if (!class_exists($class)) {
            $frameworkClass = $this->app->get(Framework::class)->completeClassName($className);
        }

        if (!class_exists($class)) {
            if (!class_exists($frameworkClass)) {
                throw new LogicException("No class for $controller $controller, [$class, $frameworkClass]");
            }
            $class = $frameworkClass;
        }

        $url = '';
        if (!method_exists($class, $method)) {
            if (!method_exists($class, '__process')) {
                return "$controller/$method not found";
            }
            $url = substr($uri, strlen($controller)+2);
            $method = '__process';
        } else {
            $url = substr($uri, (strlen($controller) + strlen($method) + 3));
        }

        $container = $this->app->get(Container::class);
        $result = $container->call([$container->get($class), $method], ['url' => $url]);

        if (is_array($result) || is_object($result)) {
            return json_encode($result);
        } else {
            return $result;
        }
    }

    public function getChain(string $uri) : array
    {
        list($clean) = explode('?', $uri);
        $chain = [];
        foreach (explode('/', $clean) as $k => $v) {
            if ($v) {
                $chain[] = $v;
            }
        }

        if (!count($chain)) {
            $chain[] = 'index';
        }

        if (count($chain) == 1) {
            $chain[] = 'index';
        }

        return $chain;
    }
}
