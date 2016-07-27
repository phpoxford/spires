<?php

namespace Spires\Tests\Core;

use Spires\Contracts\Core\UndefinedConfigKeyException;
use Spires\Core\Core;
use Spires\Core\Plugin;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function register_a_service_provider()
    {
        $core = new Core();
        $core->register(FirstServiceProviderStub::class);

        assertThat($core->getLoadedProviders(), is(hasKey(FirstServiceProviderStub::class)));
        assertThat($core->make('registerFirst'), is('First registration'));
    }

    /**
     * @test
     */
    public function register_multiple_service_providers()
    {
        $core = new Core();
        $core->register(FirstServiceProviderStub::class);
        $core->register(SecondServiceProviderStub::class);

        assertThat($core->getLoadedProviders(), is(hasKey(FirstServiceProviderStub::class)));
        assertThat($core->getLoadedProviders(), is(hasKey(SecondServiceProviderStub::class)));
        assertThat($core->make('registerSecond'), is('Second registration'));
    }

    /**
     * @test
     */
    public function boot_core()
    {
        $core = new Core();
        $core->register(FirstServiceProviderStub::class);
        $core->boot();

        assertThat($core->isBooted(), is(true));
        assertThat($core->make('bootingFirst'), is('First booted'));
    }

    /**
     * @test
     */
    public function boot_service_provider_registered_after_core_boot()
    {
        $core = new Core();
        $core->boot();
        $core->register(FirstServiceProviderStub::class);

        assertThat($core->isBooted(), is(true));
        assertThat($core->make('bootingFirst'), is('First booted'));
    }

    /**
     * @test
     */
    public function method_injection_is_available_in_service_provider_boot()
    {
        $core = new Core();
        $core->register(ThirdServiceProviderStub::class);
        $core->boot();

        assertThat($core->make('injectedInBoot'), is(anInstanceOf(InjectedStub::class)));
    }

    /**
     * @test
     */
    public function default_config_when_registering_service_provider()
    {
        $core = new Core();
        $core->register(FourthServiceProviderStub::class);

        assertThat($core->make('config'), is(identicalTo([
            null,
            'default',
            '',
        ])));
    }

    /**
     * @test
     */
    public function overwrite_config_when_registering_service_provider()
    {
        $core = new Core();
        $core->register(FourthServiceProviderStub::class, [
            'fourth.firstItem' => 'First item',
            'fourth.thirdItem' => 'Third item',
        ]);

        assertThat($core->make('config'), is(identicalTo([
            'First item',
            'default',
            'Third item',
        ])));
    }

    /**
     * @test
     */
    public function passing_undefined_config_key_when_registering_service_provider()
    {
        $this->expectException(UndefinedConfigKeyException::class);
        $this->expectExceptionMessage('Undefined config keys passed to provider: [undefinedKey, anotherUndefinedKey]');

        $core = new Core();
        $core->register(FourthServiceProviderStub::class, [
            'undefinedKey' => 'Key not defined in service provider',
            'anotherUndefinedKey' => 'Key not defined in service provider',
        ]);
    }
}

/*
 * Stubs
 */

class FirstServiceProviderStub extends \Spires\Core\ServiceProvider
{
    public function register()
    {
        $this->core->bind('registerFirst', function () {
            return 'First registration';
        });
    }

    public function boot()
    {
        $this->core->bind('bootingFirst', function () {
            return 'First booted';
        });
    }

    public function plugins() { return []; }
}

class SecondServiceProviderStub extends \Spires\Core\ServiceProvider
{
    public function register()
    {
        $this->core->bind('registerSecond', function () {
            return 'Second registration';
        });
    }

    public function plugins() { return []; }
}

interface InjectedStubInterface {}

class InjectedStub implements InjectedStubInterface {}

class ThirdServiceProviderStub extends \Spires\Core\ServiceProvider
{
    public function register()
    {
        $this->core->bind(InjectedStubInterface::class, InjectedStub::class);
    }

    public function boot(InjectedStubInterface $injected)
    {
        $this->core->bind('injectedInBoot', function () use ($injected) {
            return $injected;
        });
    }

    public function plugins() { return []; }
}

class FourthServiceProviderStub extends \Spires\Core\ServiceProvider
{
    public function config()
    {
        return [
            'fourth.firstItem' => null,
            'fourth.secondItem' => 'default',
            'fourth.thirdItem' => '',
        ];
    }

    public function register()
    {
        $this->core->bind('config', function (Core $core) {
            return [
                $core['fourth.firstItem'],
                $core['fourth.secondItem'],
                $core['fourth.thirdItem'],
            ];
        });
    }

    public function plugins() { return []; }
}
