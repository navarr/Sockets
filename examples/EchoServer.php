<?php

// run composer install in top directory
require_once __DIR__.'/../vendor/autoload.php';

use Navarr\Socket\Server;
use Navarr\Socket\Socket;

class EchoServer extends Server
{
    public function __construct($address = null, $port = 7)
    {
        parent::__construct($address, 7);
        $this->addHook(Server::HOOK_CONNECT, [$this, 'onConnect']);
        $this->addHook(Server::HOOK_INPUT, [$this, 'onInput']);
        $this->addHook(Server::HOOK_DISCONNECT, [$this, 'onDisconnect']);
        $this->run();
    }

    public function onConnect(Server $server, Socket $client, $message)
    {
        echo 'Connection Established', "\n";
    }

    public function onInput(Server $server, Socket $client, $message)
    {
        echo 'Received "', $message, '"', "\n";
        $client->write($message, strlen($message));
    }

    public function onDisconnect(Server $server, Socket $client, $message)
    {
        echo 'Disconnection', "\n";
    }
}

$server = new EchoServer('0.0.0.0');
