<?php

namespace Spires\Tests\Plugins\BangMessage;

use Spires\Plugins\BangMessage\Inbound\BangMessage;
use Spires\Plugins\BangMessage\Plugin;
use Spires\Plugins\Message\Inbound\Message;
use Spires\Tests\Resources\SpiresTestCase;

class PluginTest extends SpiresTestCase
{
    /**
     * @test
     */
    public function recognize_just_a_bang_command()
    {
        $plugin = new Plugin();
        $message = Message::from($this->newRawMessage("!foo"));
        $bang = $plugin->createBangMessage($message);

        assertThat($bang, is(anInstanceOf(BangMessage::class)));
    }

    /**
     * @test
     */
    public function recognize_bang_command_with_additional_text()
    {
        $plugin = new Plugin();
        $message = Message::from($this->newRawMessage("!foo hello world"));
        $bang = $plugin->createBangMessage($message);

        assertThat($bang, is(anInstanceOf(BangMessage::class)));
    }

    /**
     * @test
     */
    public function do_not_recognize_if_just_a_bang()
    {
        $plugin = new Plugin();
        $message = Message::from($this->newRawMessage("!"));
        $bang = $plugin->createBangMessage($message);

        assertThat($bang, is(nullValue()));
    }

    /**
     * @test
     */
    public function do_not_recognize_if_space_after_bang()
    {
        $plugin = new Plugin();
        $message = Message::from($this->newRawMessage("! foo"));
        $bang = $plugin->createBangMessage($message);

        assertThat($bang, is(nullValue()));
    }
}
