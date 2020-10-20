<?php

namespace Basis;

use Basis\Registry;
use Closure;
use Exception;

class Application
{
    use Toolkit;

    protected self $app;
    protected string $name;
    protected string $root;
    protected array $finalizers = [];

    public function __construct(self $parent = null)
    {
        $this->app = $this;
        $this->name = getenv('SERVICE_NAME');
        $this->root = getcwd();

        if (!$this->name) {
            throw new Exception("SERVICE_NAME is null", 1);
        }

        if ($parent) {
            $converter = $parent->get(Converter::class);
            $registry = $parent->get(Registry::class);
        } else {
            $converter = new Converter();
            $registry = new Registry($this, $converter);
        }

        $this->container = (new Container($registry))
            ->share(Converter::class, $converter)
            ->share(self::class, $this)
            ->share(static::class, $this);

        if ($parent) {
            $this->container->share('parent', $parent);
        }

        foreach ($registry->listClasses('configuration') as $class) {
            $this->container->call($class, 'init');
        }

        register_shutdown_function([$this, 'finalize']);
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

    public function fork()
    {
        return new Application($this);
    }

    public function finalize()
    {
        while ($callback = array_pop($this->finalizers)) {
            $this->call($callback, null);
        }
    }

    public function registerFinalizer(Closure $callback): self
    {
        $this->finalizers[] = $callback;

        return $this;
    }
}
