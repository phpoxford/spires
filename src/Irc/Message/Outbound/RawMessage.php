<?php
declare(strict_types=1);

namespace Spires\Irc\Message\Outbound;

class RawMessage implements \JsonSerializable
{
    protected $command;
    protected $params;

    public function __construct(string $command, string $params)
    {
        $this->command = $command;
        $this->params = $params;
    }

    public function command() : string
    {
        return $this->command;
    }

    public function params() : string
    {
        return $this->params;
    }

    public function __toString() : string
    {
        return trim($this->command() . ' ' . $this->params);
    }

    public function toArray() : array
    {
        return [
            'command' => $this->command,
            'params' => $this->params,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
