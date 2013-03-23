# Sockets

Sockets is a PHP Library intent on making working with PHP Sockets easier, including the creation and management of a Socket Server.

## Work in Progress

The code is currently still a work in progress, with the Socket class itself not yet fully complete.  There is a lot I still need to understand about how sockets work both in PHP and probably in C in order to make everything work amazingly.

Not everything is tested yet, and not everything works properly yet.

## Usage of SocketServer

Using SocketServer is supposed to be an easy and trivial task (and the class should be documented enough to understand what it's doing without me).

```php
<?php

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

$server = new Server('0.0.0.0', 9000);
$server->addHook(
    Server::HOOK_CONNECT,
    function (Server $server, Socket $client, $message) {
        echo 'Connection Established!',"\n";
        $message = "Hello, and welcome!  This is an ECHO Server\n";
        $client->write($message, strlen($message));
        return true;
    }
);
$server->addHook(
    Server::HOOK_INPUT,
    function (Server $server, Socket $client, $message) {
        echo 'Input Received: ',$message,"\n";
        $client->write($message, strlen($message));
        if (trim($message) == 'exit') {
            return Server::RETURN_HALT_SERVER;
        }
        return true;
    }
);
$server->run();
```
