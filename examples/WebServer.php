<?php

// run composer install in top directory
require_once __DIR__.'/../vendor/autoload.php';

use Navarr\Socket\Server;
use Navarr\Socket\Socket;

class WebServer extends Server
{
    /** @var WebClient[] */
    protected $clientMap;
    protected $readType = PHP_BINARY_READ;

    public function __construct($address = null, $port = 80)
    {
        parent::__construct($address, $port);
        $this->addHook(Server::HOOK_CONNECT, [$this, 'onConnect']);
        $this->addHook(Server::HOOK_INPUT, [$this, 'onInput']);
        $this->addHook(Server::HOOK_DISCONNECT, [$this, 'onDisconnect']);
        $this->run();
    }

    public function onConnect(Server $server, Socket $client, $message)
    {
        echo "Connection\n";
        $this->clientMap[(string) $client] = new WebClient($server, $client);
    }

    public function onInput(Server $server, Socket $client, $message)
    {
        $messages = explode("\n", $message);
        foreach ($messages as $message) {
            $message .= "\n";
            $this->clientMap[(string) $client]->dispatch($message);
        }
    }

    public function onDisconnect(Server $server, Socket $client, $message)
    {
        echo "Disconnect\n";
        unset($this->clientMap[(string) $client]);
    }
}

class WebClient
{
    protected $server = null;
    protected $socket = null;
    protected $firstLine = null;
    protected $verb = null;
    protected $resource = null;
    protected $lastLine = null;
    protected $protocol = null;
    protected $headers = [];

    public function __construct(Server $server, Socket $client)
    {
        $this->server = $server;
        $this->socket = $client;
    }

    /**
     * @param string $message
     */
    public function dispatch($message)
    {
        echo trim($message), "\n";
        $message = trim($message);
        if ($this->firstLine === null) {
            $tokens = explode(' ', $message, 3);
            $this->verb = $tokens[0];
            $this->resource = $tokens[1];
            $this->protocol = $tokens[2];

            $this->firstLine = $message;
            $this->lastLine = $message;

            return;
        }
        if ($message !== '') {
            $tokens = explode(': ', $message, 2);
            $this->headers[$tokens[0]] = $tokens[1];
        }
        if ($this->lastLine === '' && $message === '') {
            $this->writeLine('HTTP/1.1 200 OK');
            $this->writeLine('Content-Type: text/plain');
            $this->writeLine();

            $url = $this->headers['Host'].$this->resource;
            $this->writeLine(
                "You requested {$url} using verb {$this->verb} over {$this->protocol}"
            );

            $this->disconnect();
        }
        $this->lastLine = $message;
    }

    protected function disconnect()
    {
        $this->server->disconnect($this->socket);
    }

    protected function writeLine($message = '')
    {
        $message .= "\r\n";
        $this->socket->write($message, strlen($message));
    }
}

$server = new WebServer('0.0.0.0', 8001);
