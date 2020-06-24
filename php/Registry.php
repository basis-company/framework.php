<?php

namespace Basis;

use Closure;

interface Registry
{
    public function getClass(string $path): ?string;
    public function getClosureTypes(Closure $closure): array;
    public function getMethodTypes(string $class, string $method): array;
    public function getPath(string $path): ?string;
    public function getPropertyDefaultValue(string $class, string $name);
    public function getPublicMethods(string $class): array;
    public function getReflectionParameters($reflection): array;
    public function getReturnType(string $class, string $method): string;
    public function getStaticPropertyValue(string $class, string $name);
    public function hasConstructor(string $class): bool;
    public function isAbstract(string $class): bool;
    public function listClasses(string $namespace, bool $recursive = false): array;
    public function listFiles(string $path): array;
}
