<?php

namespace Spires\Tests\Container;

use stdClass;
use Spires\Container\Container;
use Spires\Contracts\Container\BindingResolutionException;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function resolves_concrete()
    {
        $container = new Container;
        $class = $container->make(ConcreteStub::class);

        assertThat($class, is(anInstanceOf(ConcreteStub::class)));
    }

    /**
     * @test
     */
    public function resolves_closure()
    {
        $container = new Container;
        $container->bind('pokemon', function () {
            return 'Charmander';
        });

        assertThat($container->make('pokemon'), is('Charmander'));
    }

    /**
     * @test
     */
    public function resolves_shared_closure()
    {
        $container = new Container;
        $class = new stdClass;
        $container->singleton('class', function () use ($class) {
            return $class;
        });

        assertThat($container->make('class'), is(sameInstance($class)));
    }

    /**
     * @test
     */
    public function resolves_shared_concrete()
    {
        $container = new Container;
        $container->singleton(ConcreteStub::class);
        $stub1 = $container->make(ConcreteStub::class);
        $stub2 = $container->make(ConcreteStub::class);

        assertThat($stub1, is(sameInstance($stub2)));
    }

    /**
     * @test
     */
    public function bind_existing_instance()
    {
        $container = new Container;
        $class = new stdClass;
        $container->instance('class', $class);

        assertThat($container->make('class'), is(sameInstance($class)));
    }

    /**
     * @test
     */
    public function resolves_concrete_from_abstract()
    {
        $container = new Container;
        $container->bind(InterfaceStub::class, ImplementationStub::class);
        $class = $container->make(InterfaceStub::class);

        assertThat($class, is(anInstanceOf(ImplementationStub::class)));
    }

    /**
     * @test
     */
    public function resolves_constructor_dependencies()
    {
        $container = new Container;
        $container->bind(InterfaceStub::class, ImplementationStub::class);
        $class = $container->make(DependentStub::class);

        assertThat($class->first, is(anInstanceOf(ImplementationStub::class)));
    }

    /**
     * @test
     */
    public function resolves_nested_constructor_dependencies()
    {
        $container = new Container;
        $container->bind(InterfaceStub::class, ImplementationStub::class);
        $class = $container->make(NestedDependentStub::class);

        assertThat($class->first, is(anInstanceOf(DependentStub::class)));
        assertThat($class->first->first, is(anInstanceOf(ImplementationStub::class)));
    }

    /**
     * @test
     */
    public function container_is_passed_to_bound_closures()
    {
        $container = new Container;
        $container->bind('something', function ($c) {
            return $c;
        });
        $c = $container->make('something');

        assertThat($c, is(sameInstance($c)));
    }

    /**
     * @test
     */
    public function parameters_can_be_passed_through_to_closure()
    {
        $container = new Container;
        $container->bind('foo', function ($c, $parameters) {
            return $parameters;
        });

        assertThat($container->make('foo', [1, 2, 3]), is([1, 2, 3]));
    }

    /**
     * @test
     */
    public function resolution_of_default_parameters()
    {
        $container = new Container;
        $class = $container->make(DefaultValueStub::class);

        assertThat($class->first, is(anInstanceOf(ConcreteStub::class)));
        assertThat($class->second, is('Second default value'));
    }

    /**
     * @test
     */
    public function fails_resolving_unbound_interface()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target ['.InterfaceStub::class.'] is not instantiable.');

        $container = new Container;
        $container->make(InterfaceStub::class, []);
    }
    /**
     * @test
     */
    public function fails_resolving_unbound_nested_interface()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target ['.InterfaceStub::class.'] is not instantiable while building ['.DependentStub::class.'].');

        $container = new Container;
        $container->make(DependentStub::class, []);
    }

    /**
     * @test
     */
    public function resolve_dependencies_for_closure()
    {
        $container = new Container;
        $result = $container->call(function (ConcreteStub $first, $second = []) {
            return compact('first', 'second');
        });

        assertThat($result['first'], is(anInstanceOf(ConcreteStub::class)));
        assertThat($result['second'], is([]));
    }

    /**
     * @test
     */
    public function resolve_dependencies_for_closure_and_overwrite_parameter()
    {
        $container = new Container;
        $result = $container->call(function (ConcreteStub $first, $second = []) {
            return compact('first', 'second');
        }, ['second' => 'Second value']);

        assertThat($result['first'], is(anInstanceOf(ConcreteStub::class)));
        assertThat($result['second'], is('Second value'));
    }

    /**
     * @test
     */
    public function resolve_dependencies_for_method_on_object()
    {
        $container = new Container;
        $object = $container->make(MethodInjectionStub::class);
        $result = $container->call([$object, 'inject']);

        assertThat($result['first'], is(anInstanceOf(ConcreteStub::class)));
        assertThat($result['second'], is('Second default value'));
    }

    /**
     * @test
     */
    public function resolve_dependencies_for_method_on_object_and_overwrite_parameters_in_order()
    {
        $container = new Container;
        $object = $container->make(MethodInjectionStub::class);
        $result = $container->call([$object, 'injectComplex'], ['Second value', 'Third value']);

        assertThat($result['first'], is(anInstanceOf(ConcreteStub::class)));
        assertThat($result['second'], is('Second value'));
        assertThat($result['third'], is('Third value'));
        assertThat($result['fourth'], is('Fourth default value'));
        assertThat($result['fifth'], is('Fifth default value'));
    }

    /**
     * @test
     */
    public function resolve_dependencies_for_method_on_object_and_overwrite_parameters_named()
    {
        $container = new Container;
        $object = $container->make(MethodInjectionStub::class);
        $result = $container->call([$object, 'injectComplex'], ['second' => 'Second value', 'third' => 'Third value', 'fifth' => 'Fifth value']);

        assertThat($result['first'], is(anInstanceOf(ConcreteStub::class)));
        assertThat($result['second'], is('Second value'));
        assertThat($result['third'], is('Third value'));
        assertThat($result['fourth'], is('Fourth default value'));
        assertThat($result['fifth'], is('Fifth value'));
    }
}

class ConcreteStub {}

interface InterfaceStub{}

class ImplementationStub implements InterfaceStub {}

class DependentStub
{
    public $first;

    public function __construct(InterfaceStub $first)
    {
        $this->first = $first;
    }
}

class NestedDependentStub
{
    public $first;

    public function __construct(DependentStub $first)
    {
        $this->first = $first;
    }
}

class DefaultValueStub
{
    public $first;
    public $second;

    public function __construct(ConcreteStub $first, $second = 'Second default value')
    {
        $this->first = $first;
        $this->second = $second;
    }
}

class MethodInjectionStub
{
    public function inject(ConcreteStub $first, $second = 'Second default value')
    {
        return compact('first', 'second');
    }

    public function injectComplex(ConcreteStub $first, $second, $third, $fourth = 'Fourth default value', $fifth = 'Fifth default value')
    {
        return compact('first', 'second', 'third', 'fourth', 'fifth');
    }
}
