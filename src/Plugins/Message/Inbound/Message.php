<?php
declare(strict_types=1);

namespace Spires\Plugins\Message\Inbound;

use Spires\Irc\Message\Inbound\RawMessage;

class Message extends RawMessage
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

    public function hasTarget($target)
    {
        return in_array($target, $this->targets());
    }
}
