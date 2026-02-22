<?php

declare(strict_types=1);

namespace App\Core\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;

class Container
{
    private static ?self $instance = null;
    private array $bindings = [];
    private array $instances = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $abstract, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    public function get(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if ($concrete instanceof Closure) {
                $object = $concrete($this);
                $this->instances[$abstract] = $object;
                return $object;
            }
            if (is_string($concrete)) {
                 if ($abstract === $concrete) {
                     return $this->build($concrete);
                 }
                 return $this->get($concrete);
            }
        }

        return $this->build($abstract);
    }

    private function build(string $concrete): object
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
             throw new Exception("Class {$concrete} not found.");
        }

        if (!$reflector->isInstantiable()) {
             throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    private function resolveDependencies(array $dependencies): array
    {
        $results = [];
        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                 if ($dependency->isDefaultValueAvailable()) {
                     $results[] = $dependency->getDefaultValue();
                     continue;
                 }
                 throw new Exception("Unresolvable dependency {$dependency->getName()} in class {$dependency->getDeclaringClass()->getName()}");
            }

            $results[] = $this->get($type->getName());
        }
        return $results;
    }
}
