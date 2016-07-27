<?php
declare(strict_types=1);

namespace Spires\Plugins\ChannelOperations\Inbound;

use Spires\Plugins\SystemMessage\Inbound\SystemMessage;

class JoinSystemMessage extends SystemMessage
{
    public function targets() : array
    {
        return explode(',', $this->params());
    }
}
