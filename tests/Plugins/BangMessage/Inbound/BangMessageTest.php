<?php
/**
 * Tests for the BangMessage class
 */

namespace Spires\Tests\Plugins\BangMessage\Inbound;

use Spires\Tests\Resources\SpiresTestCase;
use Spires\Plugins\BangMessage\Inbound\BangMessage;

class BangMessageTest extends SpiresTestCase
{
    /**
     * @test
     */
    function it_provides_the_bang_command()
    {
        $bangMessage = BangMessage::from($this->newRawMessage("!foo hello world"));
        assertThat($bangMessage->bangCommand(), is("foo"));
    }

    /**
     * @test
     */
    function it_provides_the_bang_params()
    {
        $bangMessage = BangMessage::from($this->newRawMessage("!foo hello world"));
        assertThat($bangMessage->bangText(), is("hello world"));
    }
}
