<?php
declare(strict_types=1);

namespace Spires\Plugins\SystemMessage;

use Spires\Irc\Message\Inbound\RawMessage;
use Spires\Plugins\SystemMessage\Inbound\SystemMessage;

class Plugin
{
    /**
     * @param RawMessage $message
     * @return null|SystemMessage
     */
    public function createSystemMessage(RawMessage $message)
    {
        if ($message->command() !== 'PRIVMSG') {
            return SystemMessage::from($message);
        }

        return null;
    }
}
