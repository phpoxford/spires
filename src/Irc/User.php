<?php
declare(strict_types=1);

namespace Spires\Irc;

class User
{
    /**
     * @var string
     */
    private $nickname;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $realname;

    /**
     * @var int
     */
    private $usermode;

    public function __construct(string $nickname, string $username, string $realname, int $usermode = 0)
    {
        $this->nickname = $nickname;
        $this->username = $username;
        $this->realname = $realname;
        $this->usermode = $usermode;
    }

    public function nickname() : string
    {
        return $this->nickname;
    }

    public function username() : string
    {
        return $this->username;
    }

    public function realname() : string
    {
        return $this->realname;
    }

    public function usermode() : int
    {
        return $this->usermode;
    }
}
