<?php
declare(strict_types=1);

namespace Spires\Irc;

class Connection
{
    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $server;

    /**
     * @var int
     */
    private $port;

    public function __construct(string $channel, string $server, int $port = 6667)
    {
        $this->channel = $channel;
        $this->server = $server;
        $this->port = $port;
    }

    public function channel() : string
    {
        return $this->channel;
    }

    public function server() : string
    {
        return $this->server;
    }

    public function port() : int
    {
        return $this->port;
    }
}
