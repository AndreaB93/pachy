<?php
declare(strict_types=1);

namespace Core;

class Container
{
    private array $bindings  = [];
    private array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function() use ($abstract, $factory) {
            $this->instances[$abstract] ??= $factory($this);
            return $this->instances[$abstract];
        });
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }
        throw new \RuntimeException("No binding for: $abstract");
    }

    /** Check if a binding exists */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }
}
