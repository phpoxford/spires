<?php
declare(strict_types=1);

namespace Spires\Plugins\ChannelOperations;

use Spires\Plugins\SystemMessage\Inbound\SystemMessage;
use Spires\Plugins\ChannelOperations\Inbound\JoinSystemMessage;
use Spires\Plugins\ChannelOperations\Inbound\PartSystemMessage;

class Plugin
{
    /**
     * @param SystemMessage $message
     * @return null|JoinSystemMessage
     */
    public function createJoin(SystemMessage $message)
    {
        if ($message->command() === 'JOIN') {
            return JoinSystemMessage::from($message);
        }

        return null;
    }

    /**
     * @param SystemMessage $message
     * @return null|PartSystemMessage
     */
    public function createPart(SystemMessage $message)
    {
        if ($message->command() === 'PART') {
            return PartSystemMessage::from($message);
        }

        return null;
    }
}
