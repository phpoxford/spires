<?php
declare(strict_types=1);

namespace Spires\Irc;

class Parser
{
    /**
     * Parse a raw message following the message format defined in
     * RFC 2812 for the Internet Relay Chat: Client Protocol
     * http://tools.ietf.org/html/rfc2812#section-2.3.1
     *
     * @param string $raw
     * @return array
     */
    public function parse(string $raw): array
    {
        // =  %x0D %x0A   ; "carriage return" "linefeed"
        $crlf = '\r\n';

        // =  %x41-5A / %x61-7A  ; A-Z / a-z
        $letter = 'A-Z|a-z';

        // =  %x30-39   ; 0-9
        $digit = '0-9';

        // =  digit / "A" / "B" / "C" / "D" / "E" / "F"
        $hexdigit = "$digit|a-f|A-F";

        // =  %x5B-60 / %x7B-7D   ; "[", "]", "\", "`", "_", "^", "{", "|", "}"
        $special = '\x5B-\x60|\x7B-\x7D';

        // =  %x01-09 / %x0B-0C / %x0E-1F / %x21-39 / %x3B-FF   ; any octet except NUL, CR, LF, " " and ":"
        $nospcrlfcl = '\x01-\x09|\x0B-\x0C|\x0E-\x1F|\x21-\x39|\x3B-\xFF';

        // =  %x20   ; space character
        $space = '\x20';

        // Variables to make the following regular expressions easier to follow
        $colon = ':';
        $bang = '!';
        $at = '@';
        $dash = '-';
        $slash = '\/';
        $dot = '\.';

        // =  1*letter / 3digit
        $command = "(?:[$letter]+|[$digit]{3})";

        // =  nospcrlfcl *( ":" / nospcrlfcl )
        $middle = "(?:[$nospcrlfcl][$colon|$nospcrlfcl]*)";

        // =  *( ":" / " " / nospcrlfcl )
        $trailing = "(?:[$colon|$space|$nospcrlfcl]*)";

        // =  *14( SPACE middle ) [ SPACE ":" trailing ]
        // =/ 14( SPACE middle ) [ SPACE [ ":" ] trailing ]
        $params = "(?:(?:$space$middle){0,14}(?:$space$colon$trailing)?" .
            "|(?:$space$middle){14}(?:$space(?:$colon)?$trailing)?)";

        // =  ( letter / digit ) *( letter / digit / "-" ) *( letter / digit )
        $shortname = "(?:[$letter$digit][$letter$digit$dash$slash]*[$letter$digit]*)";

        // =  shortname *( "." shortname )
        $hostname = "(?:$shortname(?:$dot$shortname)*)";

        // =  hostname
        $servername = "$hostname";

        // =  1*3digit "." 1*3digit "." 1*3digit "." 1*3digit
        $ip4addr = "(?:(?:[$digit]{1,3})$dot(?:[$digit]{1,3})$dot(?:[$digit]{1,3})$dot(?:[$digit]{1,3}))";

        // =  1*hexdigit 7( ":" 1*hexdigit )
        // =/ "0:0:0:0:0:" ( "0" / "FFFF" ) ":" ip4addr
        $ip6addr = "(?:(?:[$hexdigit]+?(?:$colon(?:[$hexdigit]+?)){7})|(?:0:0:0:0:0:(?:0|FFFF)$colon$ip4addr))";

        // =  ip4addr / ip6addr
        $hostaddr = "(?:$ip4addr|$ip6addr)";

        // =  hostname / hostaddr
        $host = "(?:$hostname|$hostaddr)";

        // =  ( letter / special ) *8( letter / digit / special / "-" )
        //  * While the maximum length is limited to nine characters, clients
        //  * SHOULD accept longer strings as they may become used in future
        //  * evolutions of the protocol.
        //  * https://tools.ietf.org/html/rfc2812#section-1.2.1
        $nickname = "(?:[$letter$special][$letter$digit$special$dash]*)";

        // =  1*( %x01-09 / %x0B-0C / %x0E-1F / %x21-3F / %x41-FF )   ; any octet except NUL, CR, LF, " " and "@"
        $user = "(?:[\x01-\x09|\x0B-\x0C|\x0E-\x1F|\x21-\x3F|\x41-\xFF]+)";

        // =  servername / ( nickname [ [ "!" user ] "@" host ] )
        $prefix = "(?:(?P<servername>$servername)" .
            "|(?:(?P<nickname>$nickname)(?:$bang(?P<username>$user))?(?:$at(?P<hostname>$host))?))";

        // =  [ ":" prefix SPACE ] command [ params ] crlf
        $message = "(?P<prefix>$colon$prefix$space)?(?P<command>$command)(?P<params>$params)?$crlf";

        // Do the thing
        preg_match("/^$message\$/SU", $raw, $matches);

        // Trim whitespace
        $matches = array_map('trim', $matches);

        // Return only the named matches we want in the order we want
        return [
            'nickname' => $matches['nickname'] ?? '',
            'username' => $matches['username'] ?? '',
            'hostname' => $matches['hostname'] ?? '',
            'serverName' => $matches['serverName'] ?? '',
            'command' => $matches['command'] ?? '',
            'params' => $matches['params'] ?? '',
        ];
    }
}
