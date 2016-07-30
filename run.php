<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Spires\Irc\Client;

// Report all PHP errors.
error_reporting(E_ALL);
// Allow the script to hang around waiting for connections.
set_time_limit(0);
// Turn on implicit output flushing so we see what we're getting as it comes in.
ob_implicit_flush();

// Create new instance of the core
$core = new \Spires\Core\Core(realpath(__DIR__));

// Core providers
$core->register(\Spires\Irc\ServiceProvider::class, [
    'connection.channel' => '##martinsbottesting',
    'connection.server' => 'irc.freenode.com',
    'connection.port' => 6667,
    'user.nickname' => 'spires',
    'user.username' => 'spiresbot',
    'user.realname' => 'Spires ALPHA',
]);
$core->registerBaseServiceProviders();

// Additional providers
//$core->register(\YourNamespace\Spires\PluginName\ServiceProvider::class);

// Boot the providers
$core->boot();

$client = $core->make(Client::class);

$client->logCore($core);

$client->connect();

$client->run();
