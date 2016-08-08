<?php
declare(strict_types=1);

namespace Spires\Plugins\BangMessage;

use Spires\Plugins\Message\Inbound\Message;
use Spires\Plugins\BangMessage\Inbound\BangMessage;

class Plugin
{
    /**
     * @param Message $message
     * @return null|BangMessage
     */
    public function createBangMessage(Message $message)
    {
        if (preg_match('/^![^\s]/', $message->text())) {
            return BangMessage::from($message);
        }

        return null;
    }
}
