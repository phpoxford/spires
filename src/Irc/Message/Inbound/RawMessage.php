<?php
declare(strict_types=1);

namespace Spires\Irc\Message\Inbound;

class RawMessage extends \Spires\Irc\Message\Outbound\RawMessage
{
    protected $nickname;
    protected $username;
    protected $hostname;
    protected $serverName;

    public function __construct(
        string $nickname,
        string $username,
        string $hostname,
        string $serverName,
        string $command,
        string $params
    ) {
        $this->nickname = $nickname;
        $this->username = $username;
        $this->hostname = $hostname;
        $this->serverName = $serverName;

        parent::__construct($command, $params);
    }

    public static function from(RawMessage $message)
    {
        return new static(
            $message->nickname(),
            $message->username(),
            $message->hostname(),
            $message->serverName(),
            $message->command(),
            $message->params()
        );
    }

    public function nickname() : string
    {
        return $this->nickname;
    }

    public function username() : string
    {
        return $this->username;
    }

    public function hostname() : string
    {
        return $this->hostname;
    }

    public function serverName() : string
    {
        return $this->serverName;
    }

    public function prefix()
    {
        $prefix = '';

        $prefix .= empty($this->nickname) ? '' : ":{$this->nickname}";
        $prefix .= empty($this->username) ? '' : "!{$this->username}";
        $prefix .= empty($this->hostname) ? '' : "@{$this->hostname}";
        $prefix .= empty($this->serverName) ? '' : ":{$this->serverName}";

        return trim($prefix);
    }

    public function __toString() : string
    {
        return trim($this->prefix() . ' ' . trim($this->command() . ' ' . $this->params));
    }

    public function toArray() : array
    {
        return [
            'nickname' => $this->nickname,
            'username' => $this->username,
            'hostname' => $this->hostname,
            'serverName' => $this->serverName,
            'command' => $this->command,
            'params' => $this->params,
        ];
    }
}
