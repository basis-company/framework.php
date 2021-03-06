<?php

namespace Basis;

use Basis\Registry\Reflection;
use Closure;

class Application
{
    use Toolkit;

    protected self $app;
    protected string $name;
    protected string $root;

    public function __construct(string $root = null, string $name = null)
    {
        if (!$root) {
            $root = getcwd();
        }
        if (!$name) {
            $name = getenv('SERVICE_NAME');
        }

        $this->app = $this;
        $this->name = $name;
        $this->root = $root;

        $converter = new Converter();
        $registry = new Reflection($this, $converter);

        $this->container = (new Container($registry))
            ->share(Converter::class, $converter)
            ->share(self::class, $this)
            ->share(static::class, $this);

        foreach ($registry->listClasses('configuration') as $class) {
            $this->container->call($class, 'init');
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoot()
    {
        return $this->root;
    }
}
