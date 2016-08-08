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
        list($bangCommand, $bangText) = explode(' ', $this->text(), 2);

        return ltrim($bangCommand, '!');
    }

    /**
     * The bang text
     * e.g. the message "!somersault mouse acrobat"
     * would return "mouse acrobat" as the bang text
     *
     * @return string
     */
    public function bangText()
    {
        list($bangCommand, $bangText) = explode(' ', $this->text(), 2);

        return $bangText;
    }
}
