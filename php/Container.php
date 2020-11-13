<?php

namespace Basis;

use Basis\Registry;
use Closure;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class Container implements ContainerInterface
{
    protected $alias = [];
    protected $factory = [];
    protected $instance = [];
    protected $registry;
    protected $trace = [];

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        $this->share(Registry::class, $registry);
        $this->share(Container::class, $this);
    }

    public function call($target, ?string $method, $values = [])
    {
        if ($target instanceof Closure) {
            $clojure = $target;
            $types = $this->registry->getClosureTypes($clojure);
            $arguments = $this->collectArguments($types, $values);
            return $clojure(...$arguments);
        }

        $class = is_object($target) ? get_class($target) : $target;

        $isConstructor = !is_object($target) && $method === '__construct';
        if ($isConstructor && !$this->registry->hasConstructor($class)) {
            return new $class();
        }

        try {
            $types = $this->registry->getMethodTypes($class, $method);
            $arguments = $this->collectArguments($types, $values);
        } catch (Throwable $e) {
            $message = "Call $class::$method failure: " . $e->getMessage();
            throw new LogicException($message, 0, $e);
        }

        if ($isConstructor) {
            $reflection = new ReflectionClass($target);
            return $reflection->newInstance(...$arguments);
        }

        $instance = is_object($target) ? $target : $this->get($target);
        return call_user_func_array([ $instance, $method ], $arguments);
    }

    public function collectArguments($types, $values = []): array
    {
        $arguments = [];
        foreach ($types as $name => $type) {
            if (array_key_exists($name, $values)) {
                $arguments[] = $values[$name];
            } elseif ($type === null) {
                throw new LogicException("$name is required");
            } else {
                $arguments[] = $this->get($type);
            }
        }
        return $arguments;
    }

    public function create(string $class): object
    {
        if (array_key_exists($class, $this->factory)) {
            if (is_array($this->factory[$class])) {
                return $this->call(...$this->factory[$class]);
            }
            return $this->call($this->factory[$class], null);
        }
        return $this->call($class, '__construct');
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->instance)
            || array_key_exists($key, $this->alias)
            || array_key_exists($key, $this->factory);
    }

    public function hasInstance($key): bool
    {
        if (array_key_exists($key, $this->alias)) {
            return $this->hasInstance($this->alias[$key]);
        }
        return array_key_exists($key, $this->instance);
    }

    public function factory(string $class): self
    {
        $type = $this->registry->getReturnType($class, 'factory');
        return $this->set($type, [ $class, 'factory' ]);
    }

    public function get($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (array_key_exists($name, $this->instance)) {
            if ($this->instance[$name] !== null) {
                return $this->instance[$name];
            }
            if (count($this->trace) > 1) {
                throw new LogicException("Circular dependency " . implode(', ', $this->trace));
            }
        }

        if (array_key_exists($name, $this->alias)) {
            $instance = $this->get($this->alias[$name]);
        } else {
            array_push($this->trace, $name);
            $this->instance[$name] = null;
            $instance = $this->create($name);
            array_pop($this->trace);
        }

        $this->instance[$name] = $instance;

        return $instance;
    }

    public function share(string $name, $instance): self
    {
        if (is_callable($instance)) {
            $this->factory[$name] = $instance;
        } elseif (is_object($instance)) {
            $this->instance[$name] = $instance;
        } elseif (is_string($instance)) {
            $this->alias[$name] = $instance;
        } elseif ($instance === null) {
            throw new LogicException("$name instance should be not null");
        } else {
            throw new LogicException("$name invalid instance");
        }
        return $this;
    }

    public function drop(string $name): self
    {
        if (array_key_exists($name, $this->instance)) {
            unset($this->instance[$name]);
        }

        return $this;
    }
}
