<?php
declare(strict_types=1);

namespace Spires\Contracts\Core;

use Spires\Contracts\Container\Container;

interface Core extends Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the Laravel installation.
     *
     * @return string
     */
    public function basePath();

    /**
     * Register a service provider with the application.
     *
     * @param  \Spires\Core\ServiceProvider|string $provider
     * @param  array $config
     * @param  bool $force
     * @return \Spires\Core\ServiceProvider
     */
    public function register($provider, array $config = [], $force = false);

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders();

    /**
     * Get all plugins.
     *
     * @return array
     */
    public function getPlugins();

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot();

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted();

}
