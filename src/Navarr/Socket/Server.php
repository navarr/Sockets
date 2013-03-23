<?php

namespace Navarr\Socket;

class Server
{
    /**
     * A Multi-dimensional array of callable arrays mapped by hook name
     * @var array
     */
    protected $hooks = array();
    /**
     * IP Address
     * @var string
     */
    protected $ip;
    /**
     * Port Number
     * @var int
     */
    protected $port;
    /**
     * The Master Socket
     * @var Socket
     */
    protected $masterSocket;
    /**
     * Maximum Amount of Clients Allowed to Connect
     * @var int
     */
    protected $maxClients = PHP_INT_MAX;
    /**
     * Maximum amount of characters to read in from a socket at once
     * This integer is passed directly to socket_read
     * @var int
     */
    protected $maxRead = 1024;
    /**
     * Connected Clients
     * @var Socket[]
     */
    protected $clients = array();

    const HOOK_CONNECT = '__NAVARR_SOCKET_SERVER_CONNECT__';
    const HOOK_INPUT = '__NAVARR_SOCKET_SERVER_INPUT__';
    const HOOK_DISCONNECT = '__NAVARR_SOCKET_SERVER_DISCONNECT__';

    public function __construct($ip, $port)
    {
        set_time_limit(0);
        $this->ip = $ip;
        $this->port = $port;

        $this->masterSocket = Socket::create(AF_INET, SOCK_STREAM, 0);
        $this->masterSocket->bind($this->ip, $this->port);
        $this->masterSocket->getSockName($this->ip, $this->port);
        $this->masterSocket->listen();
    }

    public function run()
    {
        do {
            $test = $this->loopOnce();

        } while ($test);
    }

    protected function loopOnce()
    {
        // Get all the Sockets we should be reading from
        $read[0] = $this->masterSocket;
        array_merge($read, $this->clients);

        // Set up a block call to socket_select
        $write = null;
        $except = null;
        if (Socket::select($read, $write, $except, 0) === false) {
            throw new \Exception('Problem binding to Socket::select');
        }

        // If there is a new connection
        if (in_array($this->masterSocket, $read)) {
            $socket = $this->masterSocket->accept();
            $this->clients[] = $socket;
            $this->triggerHooks(self::HOOK_CONNECT, $socket);
        }

        // Check for input from each clients
        foreach ($this->clients as $client) {
            if (in_array($client, $read)) {
                $input = $client->read($this->maxRead);
                if ($input === null) {
                    $this->disconnect($client);
                } else {
                    $this->triggerHooks(self::HOOK_INPUT, $client, $input);
                }
            }
        }

        // Tells 'run' to Continue the Loop
        return true;
    }

    protected function disconnect($client, $message = '')
    {
        $clientIndex = array_search($client, $this->clients);
        $this->triggerHooks(self::HOOK_DISCONNECT, $this->clients[$clientIndex], $message);
        $this->clients[$clientIndex]->close();
        unset($this->clients[$clientIndex]);
    }

    protected function triggerHooks($command, &$client, $input = null)
    {
        if (isset($this->hooks[$command])) {
            foreach ($this->hooks[$command] as $callable) {
                $continue = call_user_func($callable, $this, $client, $input);
                if ($continue === false) {
                    break;
                }
            }
        }
    }

    public function addHook($command, $callable)
    {
        if (!isset($this->hooks[$command])) {
            $this->hooks[$command] = array();
        } else {
            $k = array_search($callable, $this->hooks[$command]);
            if ($k !== false) {
                return;
            }
        }
        $this->hooks[$command][] = $callable;
    }
}
