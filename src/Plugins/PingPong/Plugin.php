<?php
declare(strict_types=1);

namespace Spires\Plugins\PingPong;

use Spires\Plugins\PingPong\Inbound\PingSystemMessage;
use Spires\Plugins\SystemMessage\Inbound\SystemMessage;

class Plugin
{
    /**
     * @param SystemMessage $message
     * @return null|PingSystemMessage
     */
    public function createPing(SystemMessage $message)
    {
        if ($message->command() === 'PING') {
            return PingSystemMessage::from($message);
        }

        return null;
    }

    public function sendPong(PingSystemMessage $ping)
    {
        $ping->pong();
    }
}
