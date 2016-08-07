<?php
/**
 * Tests for the BangMessage class
 */

namespace Spires\Tests\Plugins\BangMessage\Inbound;

use Spires\Plugins\BangMessage\Inbound\BangMessage;
use Spires\Tests\Resources\SpiresTestCase;

class BangMessageTest extends SpiresTestCase
{
    /**
     * @test
     */
    function it_returns_the_text_minus_the_bang()
    {
        $bangMessage = BangMessage::from($this->newRawMessage("!foo hello world"));
        assertThat($bangMessage->text(), is("foo hello world"));
    }

    /**
     * @test
     */
    function it_provides_the_bang_command()
    {
        $bangMessage = BangMessage::from($this->newRawMessage("!foo hello world"));
        assertThat($bangMessage->bangCommand(), is("foo"));
    }
}
