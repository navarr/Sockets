# Sockets

Sockets is a PHP Library intent on making working with PHP Sockets easier, including the creation and management of a Socket Server.

## Work in Progress

The code is currently still a work in progress, with the Socket class itself not yet fully complete.  There is a lot I still need to understand about how sockets work both in PHP and probably in C in order to make everything work amazingly.

Not everything is tested yet, and not everything works properly yet.

It is advised not to seriously use this until I create git tag 1.0.0.  There will be breaking changes before then.

## Usage of SocketServer

Using SocketServer is supposed to be an easy and trivial task (and the class should be documented enough to understand what it's doing without me).

### Example of a String Reversal Server

```php
<?php

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

function smartWrite(Socket $client, $message, $eol = "\r\n")
{
    $message .= $eol;
    return $client->write($message, strlen($message));
}

$server = new Server('0.0.0.0', 31337);
$server->addHook(
    Server::HOOK_CONNECT,
    function (Server $server, Socket $client, $message) {
        smartWrite($client, "String? ", '');
        return true;
    }
);
$server->addHook(
    Server::HOOK_INPUT,
    function (Server $server, Socket $client, $message) {

        // Filter out \r\n's
        $trim = trim($message);
        if (empty($trim)) {
            return true;
        }

        if (strtolower($trim) == "quit") {
            smartWrite($client, "Oh... Goodbye...");
            // This is a gotcha.  These functions should probably return a Client wrapper for the Socket
            $server->disconnect($client);
            return true;
        }

        $return = strrev($trim);
        smartWrite($client, $return);
        smartWrite($client, "String? ", '');
        return true;
    }
);
$server->run();
```
