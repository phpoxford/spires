<?php
declare(strict_types=1);

namespace Spires\Plugins\Message;

use Spires\Irc\Message\Inbound\RawMessage;
use Spires\Plugins\Message\Inbound\Message;

class Plugin
{
    /**
     * @param RawMessage $message
     * @return null|Message
     */
    public function createMessage(RawMessage $message)
    {
        if ($message->command() === 'PRIVMSG') {
            return Message::from($message);
        }

        return null;
    }
}
