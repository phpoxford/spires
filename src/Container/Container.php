<?php
declare(strict_types=1);

namespace Spires\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Spires\Contracts\Container\Container as ContainerContract;
use Spires\Contracts\Container\BindingResolutionException;

class Container implements ArrayAccess, ContainerContract
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * Register a binding with the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        $abstract = $this->normalize($abstract);

        $concrete = $this->normalize($concrete);

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string|array $abstract
     * @param  \Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string $abstract
     * @param  mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance)
    {
        $abstract = $this->normalize($abstract);

        $this->instances[$abstract] = $instance;
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string $abstract
     * @return bool
     */
    public function bound(string $abstract)
    {
        $abstract = $this->normalize($abstract);

        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = [])
    {
        $abstract = $this->normalize($abstract);

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  \Closure|string $concrete
     * @param  array $parameters
     * @return mixed
     *
     * @throws \Spires\Contracts\Container\BindingResolutionException
     */
    public function build($concrete, array $parameters = [])
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            if (!empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);

                $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }

            throw new BindingResolutionException($message);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $parameters = $this->keyParametersByArgument(
            $dependencies,
            $parameters
        );

        $instances = $this->getDependencies(
            $dependencies,
            $parameters
        );

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Call the given Closure / [object, method] and inject its dependencies.
     *
     * @param  callable|array $callable
     * @param  array $parameters
     * @return mixed
     */
    public function call($callable, array $parameters = [])
    {
        $injected = $this->getInjectedMethodParameters($callable, $parameters);

        return call_user_func_array($callable, $injected);
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  \Spires\Contracts\Container\Container $container
     * @return void
     */
    public static function setInstance(ContainerContract $container)
    {
        static::$instance = $container;
    }

    /**
     * Normalize the given class name by removing leading slashes.
     *
     * @param  mixed $service
     * @return mixed
     */
    protected function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * @param  string $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param  string $abstract
     * @param  string $concrete
     * @return \Closure
     */
    protected function getClosure(string $abstract, string $concrete)
    {
        return function ($c, $parameters = []) use ($abstract, $concrete) {
            $method = ($abstract == $concrete) ? 'build' : 'make';

            return $c->$method($concrete, $parameters);
        };
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string $abstract
     * @return mixed  $concrete
     */
    protected function getConcrete(string $abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed $concrete
     * @param  string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, string $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Determine if a given type is shared.
     *
     * @param  string $abstract
     * @return bool
     */
    protected function isShared(string $abstract)
    {
        $abstract = $this->normalize($abstract);

        if (isset($this->instances[$abstract])) {
            return true;
        }

        if (!isset($this->bindings[$abstract]['shared'])) {
            return false;
        }

        return $this->bindings[$abstract]['shared'] === true;
    }

    /**
     * If extra parameters are passed by numeric ID, rekey them by argument name.
     *
     * @param  array $dependencies
     * @param  array $parameters
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);

                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array $parameters
     * @param  array $primitives
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }

    /**
     * Resolve a non-class hinted dependency.
     *
     * @param  \ReflectionParameter $parameter
     * @return mixed
     *
     * @throws \Spires\Contracts\Container\BindingResolutionException
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $message = "Unresolvable dependency resolving [$parameter] " .
            "in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param  \ReflectionParameter $parameter
     * @return mixed
     *
     * @throws \Spires\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        } catch (BindingResolutionException $e) {
            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param  callable|array $callable
     * @param  array $parameters
     * @return array
     */
    protected function getInjectedMethodParameters($callable, array $parameters = [])
    {
        $injected = [];

        foreach ($this->getCallReflector($callable)->getParameters() as $parameter) {
            $injected[$parameter->name] = $this->addDependencyForCallParameter($parameter, $parameters);
        }

        return $injected;
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|array $callable
     * @return \ReflectionFunctionAbstract
     */
    protected function getCallReflector($callable)
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @param  \ReflectionParameter $parameter
     * @param  array $parameters
     * @return mixed
     */
    protected function addDependencyForCallParameter(ReflectionParameter $parameter, array &$parameters)
    {
        if (array_key_exists($parameter->name, $parameters)) {
            $value = $parameters[$parameter->name];
            unset($parameters[$parameter->name]);
            return $value;
        } elseif ($parameter->getClass()) {
            return $this->make($parameter->getClass()->name);
        } elseif ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return array_shift($parameters);
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        // If the value is not a Closure, we will make it one. This simply gives
        // more "drop-in" replacement functionality for the Pimple which this
        // container's simplest functions are base modeled and built after.
        if (!$value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this->bind($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $key = $this->normalize($key);

        unset($this->bindings[$key], $this->instances[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
