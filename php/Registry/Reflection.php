<?php

namespace Basis\Registry;

use Basis\Application;
use Basis\Converter;
use Basis\Registry;
use Closure;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

class Reflection implements Registry
{
    protected array $prefixes;
    protected Converter $converter;

    public function __construct(Application $app, Converter $converter)
    {
        $service = $converter->xtypeToClass($app->getName());

        $this->converter = $converter;
        $this->prefixes = [
            "Basis\\" => dirname(dirname(__DIR__)),
            '' => $app->getRoot(),
        ];
    }

    public function getClassProperties(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            if ($property->getDeclaringClass() == $reflectionClass) {
                $result[] = $property->getName();
            }
        }

        return $result;
    }

    public function getRequiredClassProperties(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            if ($property->getDeclaringClass() != $reflectionClass) {
                continue;
            }
            if ($this->getPropertyDefaultValue($class, $property->getName()) !== null) {
                continue;
            }
            $result[] = $property->getName();
        }

        return $result;
    }

    public function getStaticPropertyValue(string $class, string $name)
    {
        return (new ReflectionClass($class))->getStaticPropertyValue('events');
    }

    public function getPropertyDefaultValue(string $class, string $name)
    {
        $reflection = new ReflectionClass($class);
        $defaults = $reflection->getDefaultProperties();
        if (array_key_exists($name, $defaults)) {
            return $defaults[$name];
        }
    }

    public function getClass(string $path): ?string
    {
        $class = $this->converter->xtypeToClass($path);

        foreach ($this->prefixes as $prefix => $path) {
            if (class_exists($prefix . $class)) {
                return $prefix . $class;
            }
        }

        return null;
    }

    public function getPath(string $path): ?string
    {
        foreach ($this->prefixes as $prefix) {
            if (file_exists($prefix . '/' . $path)) {
                return $prefix . '/' . $path;
            }
        }
    }

    public function getReturnType(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        return $reflection->getReturnType();
    }

    public function getClosureTypes(Closure $closure): array
    {
        $reflection = new ReflectionFunction($closure);
        return $this->getReflectionParameters($reflection);
    }

    public function getPublicMethods(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        return $methods;
    }

    public function hasTrait($class, $trait): bool
    {
        $reflection = new ReflectionClass($class);
        return in_array($trait, $reflection->getTraitNames());
    }

    public function getMethodTypes(string $class, string $method): array
    {
        $reflection = new ReflectionMethod($class, $method);
        return $this->getReflectionParameters($reflection);
    }

    public function getReflectionParameters($reflection): array
    {
        $types = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $types[$parameter->getName()] = $type ? $type->getName() : null;
        }
        return $types;
    }

    public function hasConstructor(string $class): bool
    {
        if (method_exists($class, '__construct')) {
            $types = $this->getMethodTypes($class, '__construct');
            return count($types) > 0;
        }
        return false;
    }

    public function listClasses(string $namespace, bool $recursive = false): array
    {
        $namespace = $this->converter->xtypeToClass($namespace);
        $namespace = str_replace('\\', '/', $namespace);
        $classes = [];

        foreach ($this->prefixes as $prefix => $path) {
            $files = $this->listFiles($path . '/php/' . $namespace);
            foreach ($files as $file) {
                $class = str_replace('/', '\\', substr($file, 0, -4));
                $classes[] = $prefix . $namespace . '\\' . $class;
            }
        }
        return $classes;
    }

    public function listFiles(string $path): array
    {

        if ($path[0] !== '/') {
            $result = [];
            foreach ($this->prefixes as $prefix) {
                $result = array_merge($result, $this->listFiles($prefix . '/' . $path));
            }
            return array_unique($result);
        }

        if (!is_dir($path)) {
            return [];
        }

        $result = [];
        foreach (scandir($path) as $file) {
            if ($file != '.' && $file != '..') {
                if (is_file("$path/$file")) {
                    $result[] = $file;
                } else {
                    foreach ($this->listFiles("$path/$file") as $child) {
                        $result[] = "$file/$child";
                    }
                }
            }
        }

        return $result;
    }

    public function isAbstract(string $class): bool
    {
        return (new ReflectionClass($class))->isAbstract();
    }
}
