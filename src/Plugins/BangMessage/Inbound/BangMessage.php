<?php
declare(strict_types=1);

namespace Spires\Plugins\BangMessage\Inbound;

use Spires\Plugins\Message\Inbound\Message;

class BangMessage extends Message
{
    /**
     * The bang command
     * e.g. the message "!somersault mouse acrobat"
     * would return "somersault" as the bang command
     *
     * @return string
     */
    public function bangCommand()
    {
        return head(explode(' ', $this->text()));
    }

    public function text()
    {
        return ltrim(parent::text(), '!');
    }
}
