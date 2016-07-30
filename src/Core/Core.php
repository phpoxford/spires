<?php
declare(strict_types=1);

namespace Spires\Core;

use Spires\Container\Container;
use Spires\Contracts\Core\Core as CoreContract;
use Spires\Contracts\Core\UndefinedConfigKeyException;

class Core extends Container implements CoreContract
{
    /**
     * @var string
     */
    const VERSION = '0.1.1';

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * @param  string|null $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        $this->registerBaseBindings();

        if ($basePath) {
            $this->setBasePath($basePath);
        }
    }

    /**
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * @return string
     */
    public function basePath()
    {
        return $this->basePath;
    }

    /**
     * @param  \Spires\Core\ServiceProvider|string $provider
     * @param  array $config
     * @param  bool $force
     * @return ServiceProvider
     */
    public function register($provider, array $config = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $this->registerConfig($provider, $config);

        $this->registerProvider($provider);

        $this->markAsRegistered($provider);

        $this->addPlugins($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by the developer's application logics.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    public function registerBaseServiceProviders()
    {
        $this->register(\Spires\Plugins\SystemMessage\ServiceProvider::class);
        $this->register(\Spires\Plugins\ChannelOperations\ServiceProvider::class);
        $this->register(\Spires\Plugins\PingPong\ServiceProvider::class);
        $this->register(\Spires\Plugins\Message\ServiceProvider::class);
        $this->register(\Spires\Plugins\BangMessage\ServiceProvider::class);
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Get all plugins.
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->serviceProviders, function ($provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance(\Spires\Contracts\Core\Core::class, $this);
        $this->instance('core', $this);
        $this->instance(Container::class, $this);
    }

    /**
     * Set the base path for the application.
     *
     * @param  string $basePath
     * @return $this
     */
    protected function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        $this->instance('path.base', $this->basePath());
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Spires\Core\ServiceProvider|string $provider
     * @return \Spires\Core\ServiceProvider|null
     */
    protected function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        foreach ($this->serviceProviders as $key => $value) {
            if ($value instanceof $name) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     * @return \Spires\Core\ServiceProvider
     */
    protected function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Spires\Core\ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Add plugins from the service provider.
     *
     * @param  \Spires\Core\ServiceProvider $provider
     * @return void
     */
    protected function addPlugins($provider)
    {
        foreach ($provider->plugins() as $plugin) {
            $this->plugins[$plugin] = $this->make($plugin);
        }
    }

    /**
     * Register the given service provider.
     *
     * @param  \Spires\Core\ServiceProvider $provider
     * @param  array $config
     * @return mixed
     *
     * @throws \Spires\Contracts\Core\UndefinedConfigKeyException
     */
    protected function registerConfig(ServiceProvider $provider, array $config)
    {
        $default = $provider->config();

        if ($undefined = array_keys(array_diff_key($config, $default))) {
            throw new UndefinedConfigKeyException(
                'Undefined config keys passed to provider: [' . implode(', ', $undefined) . ']'
            );
        }

        foreach (array_merge($default, $config) as $key => $value) {
            $this->bind($key, function () use ($value) {
                return $value;
            });
        }
    }

    /**
     * Register the given service provider.
     *
     * @param  \Spires\Core\ServiceProvider $provider
     * @return mixed
     */
    protected function registerProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'register')) {
            return $provider->register();
        }
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Spires\Core\ServiceProvider $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }
}
