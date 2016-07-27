<?php
declare(strict_types=1);

namespace Spires\Plugins\BangMessage\Inbound;

use Spires\Plugins\Message\Inbound\Message;

class BangMessage extends Message
{
    public function text()
    {
        return ltrim(parent::text(), '!');
    }
}
