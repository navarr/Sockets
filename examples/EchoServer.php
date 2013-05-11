<?php

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

class EchoServer extends Server
{
    public function __construct($ip = null, $port = 7)
    {
        parent::__construct($ip, 7);
        $this->addHook(Server::HOOK_CONNECT, array($this, 'onConnect'));
        $this->addHook(Server::HOOK_INPUT, array($this, 'onInput'));
        $this->addHook(Server::HOOK_DISCONNECT, array($this, 'onDisconnect'));
        $this->run();
    }

    public function onConnect(Server $server, Socket $client, $message)
    {
        echo 'Connection Established',"\n";
    }

    public function onInput(Server $server, Socket $client, $message)
    {
        echo 'Received "',$message,'"',"\n";
        $client->write($message, strlen($message));
    }

    public function onDisconnect(Server $server, Socket $client, $message)
    {
        echo 'Disconnection',"\n";
    }
}

$server = new EchoServer('0.0.0.0');
