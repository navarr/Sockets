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

    /**
     * Type of Read to use.  One of PHP_BINARY_READ, PHP_NORMAL_READ
     * @var int
     */
    protected $readType = PHP_BINARY_READ;

    /**
     * Constant String for Generic Connection Hook
     */
    const HOOK_CONNECT = '__NAVARR_SOCKET_SERVER_CONNECT__';

    /**
     * Constant String for Generic Input Hook
     */
    const HOOK_INPUT = '__NAVARR_SOCKET_SERVER_INPUT__';

    /**
     * Constant String for Generic Disconnect Hook
     */
    const HOOK_DISCONNECT = '__NAVARR_SOCKET_SERVER_DISCONNECT__';

    /**
     * Return value from a hook callable to tell the server not to run the other hooks
     */
    const RETURN_HALT_HOOK = false;

    /**
     * Return value from a hook callable to tell the server to halt operations
     */
    const RETURN_HALT_SERVER = '__NAVARR_HALT_SERVER__';

    /**
     * Create an Instance of a Server rearing to go
     * @param string $ip
     * @param int $port
     */
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

    public function __destruct()
    {
        $this->masterSocket->close();
    }

    /**
     * Run the Server, forever
     * @return void
     * @throws \Navarr\Socket\Exception\SocketException
     */
    public function run()
    {
        do {
            $test = $this->loopOnce();

        } while ($test);

        $this->shutDownEverything();
    }

    /**
     * This is the main server loop.  This code is responsible for adding connections and triggering hooks
     * @return bool Whether or not to shutdown the erver
     * @throws \Navarr\Socket\Exception\SocketException
     */
    protected function loopOnce()
    {
        // Get all the Sockets we should be reading from
        $read[0] = $this->masterSocket;
        array_merge($read, $this->clients);

        // Set up a block call to socket_select
        $write = null;
        $except = null;
        Socket::select($read, $write, $except, 0);

        // If there is a new connection, add it
        if (in_array($this->masterSocket, $read)) {
            $socket = $this->masterSocket->accept();
            $this->clients[] = $socket;
            if ($this->triggerHooks(self::HOOK_CONNECT, $socket) === false) {
                // This only happens when a hook tells the server to shut itself down.
                return false;
            }
        }

        // Check for input from each client
        foreach ($this->clients as $client) {
            $input = $this->read($client);
            if ($input === '') {
                if ($this->disconnect($client) === false) {
                    // This only happens when a hook tells the server to shut itself down.
                    return false;
                }
            } else {
                if ($this->triggerHooks(self::HOOK_INPUT, $client, $input) === false) {
                    // This only happens when a hook tells the server to shut itself down.
                    return false;
                }
            }
        }

        // Tells self::run to Continue the Loop
        return true;
    }

    /**
     * Overrideable Read Functionality
     * @param Socket $client
     * @return bool|string
     */
    protected function read(Socket $client)
    {
        return $client->read($this->maxRead, $this->readType);
    }

    /**
     * Disconnect the supplied Client Socket
     * @param Socket $client
     * @param string $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     * @return bool Whether or not to shutdown the server
     */
    public function disconnect(Socket $client, $message = '')
    {
        $clientIndex = array_search($client, $this->clients);
        $return = $this->triggerHooks(self::HOOK_DISCONNECT, $this->clients[$clientIndex], $message);
        $this->clients[$clientIndex]->close();
        unset($this->clients[$clientIndex]);
        if ($return === self::RETURN_HALT_SERVER) {
            return false;
        }
        return true;
    }

    /**
     * Triggers the hooks for the supplied command
     * @param string $command Hook to listen for (e.g. HOOK_CONNECT, HOOK_INPUT, HOOK_DISCONNECT)
     * @param Socket $client
     * @param string $input Message Sent along with the Trigger
     * @return bool Whether or not to shutdown the server
     */
    protected function triggerHooks($command, Socket $client, $input = null)
    {
        if (isset($this->hooks[$command])) {
            foreach ($this->hooks[$command] as $callable) {
                $continue = call_user_func($callable, $this, $client, $input);
                if ($continue === self::RETURN_HALT_HOOK) {
                    break;
                }
                if ($continue === self::RETURN_HALT_SERVER) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Attach a Listener to a Hook
     * @param string $command Hook to listen for
     * @param callable $callable A callable with the signature (Server, Socket, string)
     * @return void
     */
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

    /**
     * Remove the provided Callable from the provided Hook
     * @param string $command Hook to remove callable from
     * @param callable $callable The callable to be removed
     * @return void
     */
    public function removeHook($command, $callable)
    {
        if (isset($this->hooks[$command]) && array_search($callable, $this->hooks[$command]) !== false) {
            unset($this->hooks[$command][array_search($callable, $this->hooks[$command])]);
        }
    }

    /**
     * Disconnect all the Clients and shut down the server
     * @return void
     */
    private function shutDownEverything()
    {
        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }
        $this->masterSocket->close();
    }
}
