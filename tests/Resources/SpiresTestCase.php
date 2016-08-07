<?php

namespace Spires\Tests\Resources;

use Spires\Irc\Message\Inbound\RawMessage;
use Spires\Irc\Parser;

/**
 * Custom test case for Spires stuff
 */
class SpiresTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $text
     * @return RawMessage
     */
    protected function newRawMessage($text)
    {
        $parser = new Parser();
        return RawMessage::fromArray(
            $parser->parse(":FooManChew!~foomanchew@unaffiliated/foomanchew PRIVMSG #phpoxford :{$text}\r\n")
        );
    }
}
