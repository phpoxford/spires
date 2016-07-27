<?php
declare(strict_types=1);

namespace Spires\Irc;

use Spires\Contracts\Core\Core;
use Spires\Core\Dispatcher;
use Spires\Core\Plugin;

class ServiceProvider extends \Spires\Core\ServiceProvider
{
    /**
     * Define config keys to make available with their defaults.
     *
     * @return array
     */
    public function config()
    {
        return [
            'connection.channel' => '',
            'connection.server' => '',
            'connection.port' => 6667,
            'user.nickname' => 'spires',
            'user.username' => 'spiresbot',
            'user.realname' => 'Spires ALPHA',
        ];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->core->bind(Connection::class, function (Core $core) {
            return new Connection(
                $core['connection.channel'],
                $core['connection.server'],
                $core['connection.port']
            );
        });

        $this->core->bind(User::class, function (Core $core) {
            return new User(
                $core['user.nickname'],
                $core['user.username'],
                $core['user.realname']
            );
        });

        $this->core->singleton(Client::class);
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Plugins provided.
     *
     * @return Plugin[]
     */
    public function plugins()
    {
        return [];
    }
}
