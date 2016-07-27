<?php
declare(strict_types=1);

namespace Spires\Plugins\ChannelOperations\Inbound;

use Spires\Plugins\SystemMessage\Inbound\SystemMessage;

class PartSystemMessage extends SystemMessage
{
    public function targets() : array
    {
        list($targets,) = explode(' ', $this->params(), 2);

        return explode(',', $targets);
    }

    public function text()
    {
        list(,$text) = explode(' ', $this->params(), 2);

        return ltrim($text, ':');
    }
}
