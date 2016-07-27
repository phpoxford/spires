<?php
declare(strict_types=1);

namespace Spires\Plugins\PingPong\Inbound;

use Spires\Plugins\SystemMessage\Inbound\SystemMessage;

class PingSystemMessage extends SystemMessage
{
    public function server1() : string
    {
        $servers = explode(' ', $this->params());

        return ltrim($servers[0], ':');
    }

    public function server2()
    {
        $servers = explode(' ', $this->params());

        return ltrim(($servers[1] ?? ''), ':');
    }

    public function pong()
    {
        send_command('PONG', "{$this->server1()} {$this->server2()}");
    }
}
