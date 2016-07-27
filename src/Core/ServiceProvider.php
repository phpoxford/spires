<?php
declare(strict_types=1);

namespace Spires\Core;

use BadMethodCallException;
use Spires\Contracts\Core\Core as CoreContract;

abstract class ServiceProvider
{
    /**
     * @var \Spires\Contracts\Core\Core
     */
    protected $core;

    /**
     * @param  \Spires\Contracts\Core\Core $core
     * @return void
     */
    public function __construct(CoreContract $core)
    {
        $this->core = $core;
    }

    /**
     * Define config keys with their default values.
     *
     * @return array
     */
    public function config()
    {
        return [];
    }

    /**
     * (Optional) Register the service provider.
     *
     * @return void
     */
//    public function register()
//    {
//        //
//    }

    /**
     * (Optional) Boot the service provider.
     * Parameters are resolved through the container.
     *
     * @return void
     */
//    public function boot()
//    {
//        //
//    }

    /**
     * Plugins provided.
     *
     * @return string[]
     */
    abstract public function plugins();

    /**
     * Dynamically handle missing method calls.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['register', 'boot'])) {
            return;
        }
        throw new BadMethodCallException("Call to undefined method [{$method}]");
    }
}
