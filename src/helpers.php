<?php
declare(strict_types=1);

use Spires\Core\Core;
use Spires\Irc\Client;
use Spires\Plugins\Message\Inbound\Message as InboundMessage;
use Spires\Plugins\Message\Outbound\Message;
use Spires\Plugins\SystemMessage\Outbound\SystemMessage;

function core(string $abstract = null, array $parameters = [])
{
    if (is_null($abstract)) {
        return Core::getInstance();
    }

    return Core::getInstance()->make($abstract, $parameters);
}

function send_command(string $command, string $params)
{
    core(Client::class)->write((string) new SystemMessage($command, $params));
}

function send_to(array $targets, string $text)
{
    core(Client::class)->write((string) new Message($targets, $text));
}

function reply(string $text)
{
    $myNick = core(Client::class)->user()->nickname();
    $senderNick = core(InboundMessage::class)->nickname();
    $targets = core(InboundMessage::class)->targets();
    $targets = array_map(function ($target) use ($myNick, $senderNick) {
        return $target === $myNick ? $senderNick : $target;
    }, $targets);

    send_to($targets, $text);
}

/**
 * Returns the first element of the given array
 *
 * @param array $array
 */
function head($array)
{
    return array_values($array)[0];
}
