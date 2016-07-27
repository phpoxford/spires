<?php
declare(strict_types=1);

namespace Spires\Contracts\Container;

interface Container
{
    /**
     * Register a binding with the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false);

    /**
     * Register a shared binding in the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null);

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string $abstract
     * @param  mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance);

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string $abstract
     * @return bool
     */
    public function bound(string $abstract);

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  \Closure|string $concrete
     * @param  array $parameters
     * @return mixed
     *
     * @throws \Spires\Contracts\Container\BindingResolutionException
     */
    public function build($concrete, array $parameters = []);

    /**
     * Call the given Closure / [object, method] and inject its dependencies.
     *
     * @param  callable|array $callable
     * @param  array $parameters
     * @return mixed
     */
    public function call($callable, array $parameters = []);

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance();

    /**
     * Set the shared instance of the container.
     *
     * @param  \Spires\Contracts\Container\Container $container
     * @return void
     */
    public static function setInstance(Container $container);
}
