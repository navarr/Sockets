<?php

namespace Navarr\Socket;

use Navarr\Socket\Exception\SocketException;

class Server
{
    /**
     * A Multi-dimensional array of callable arrays mapped by hook name.
     *
     * @var array<string, callable[]>
     */
    protected array $hooks = [];

    /**
     * IP Address.
     *
     * @var string
     */
    protected string $address;

    /**
     * Port Number.
     *
     * @var int
     */
    protected int $port;

    /**
     * Seconds to wait on a socket before timing out.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Domain.
     *
     * @see http://php.net/manual/en/function.socket-create.php
     *
     * @var int One of AF_INET, AF_INET6, AF_UNIX
     */
    protected int $domain;

    /**
     * The Master Socket.
     *
     * @var ?Socket
     */
    protected ?Socket $masterSocket = null;

    /**
     * Maximum Amount of Clients Allowed to Connect.
     *
     * @var int
     */
    protected int $maxClients = PHP_INT_MAX;

    /**
     * Maximum amount of characters to read in from a socket at once
     * This integer is passed directly to socket_read.
     *
     * @var int
     */
    protected int $maxRead = 1024;

    /**
     * Connected Clients.
     *
     * @var Socket[]
     */
    protected array $clients = [];

    /**
     * Type of Read to use.  One of PHP_BINARY_READ, PHP_NORMAL_READ.
     *
     * @var int
     */
    protected int $readType = PHP_BINARY_READ;

    /**
     * Constant String for Generic Connection Hook.
     */
    public const HOOK_CONNECT = '__NAVARR_SOCKET_SERVER_CONNECT__';

    /**
     * Constant String for Generic Input Hook.
     */
    public const HOOK_INPUT = '__NAVARR_SOCKET_SERVER_INPUT__';

    /**
     * Constant String for Generic Disconnect Hook.
     */
    public const HOOK_DISCONNECT = '__NAVARR_SOCKET_SERVER_DISCONNECT__';

    /**
     * Constant String for Server Timeout.
     */
    public const HOOK_TIMEOUT = '__NAVARR_SOCKET_SERVER_TIMEOUT__';

    /**
     * Return value from a hook callable to tell the server not to run the other hooks.
     */
    public const RETURN_HALT_HOOK = false;

    /**
     * Return value from a hook callable to tell the server to halt operations.
     */
    public const RETURN_HALT_SERVER = '__NAVARR_HALT_SERVER__';

    /**
     * Setup the configuration for the server
     *
     * @param string $address An IPv4, IPv6, or Unix socket address
     * @param int $port
     * @param ?int $timeout Seconds to wait on a socket before timing it out
     * @throws SocketException
     */
    public function __construct(string $address, int $port = 0, ?int $timeout = 0)
    {
        $this->address = $address;
        $this->port = $port;
        $this->timeout = $timeout;

        switch (true) {
            case filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4):
                $this->domain = AF_INET;
                break;
            case filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6):
                $this->domain = AF_INET6;
                break;
            default:
                $this->domain = AF_UNIX;
        }
    }

    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see run} you do not need to call this method.
     *
     * @throws SocketException
     */
    public function start(): void
    {
        set_time_limit(0);
        $this->masterSocket = Socket::create($this->domain, SOCK_STREAM, 0);
        $this->masterSocket->bind($this->address, $this->port);
        $this->masterSocket->getSockName($this->address, $this->port);
        $this->masterSocket->listen();
    }

    public function __destruct()
    {
        $this->masterSocket?->close();
    }

    /**
     * Run the Server for as long as loopOnce returns true.
     *
     * @throws SocketException
     * @see loopOnce
     */
    public function run(): void
    {
        if ($this->masterSocket === null) {
            $this->start();
        }

        do {
            $test = $this->loopOnce();
        } while ($test);

        $this->shutDownEverything();
    }

    /**
     * This is the main server loop.  This code is responsible for adding connections and triggering hooks.
     *
     * @return bool Whether or not to shutdown the server
     * @throws SocketException
     */
    protected function loopOnce(): bool
    {
        // Get all the Sockets we should be reading from
        $read = array_merge([$this->masterSocket], $this->clients);

        // Set up a block call to socket_select
        $write = null;
        $except = null;
        $ret = Socket::select($read, $write, $except, $this->timeout);
        if (
            !is_null($this->timeout)
            && $ret === 0
            && $this->triggerHooks(self::HOOK_TIMEOUT, $this->masterSocket) === false
        ) {
            // This only happens when a hook tells the server to shut itself down.
            return false;
        }

        // If there is a new connection, add it
        if (in_array($this->masterSocket, $read)) {
            unset($read[array_search($this->masterSocket, $read)]);
            $socket = $this->masterSocket->accept();
            $this->clients[] = $socket;

            if ($this->triggerHooks(self::HOOK_CONNECT, $socket) === false) {
                // This only happens when a hook tells the server to shut itself down.
                return false;
            }
            unset($socket);
        }

        // Check for input from each client
        foreach ($read as $client) {
            $input = $this->read($client);

            if ($input === '') {
                if ($this->disconnect($client) === false) {
                    // This only happens when a hook tells the server to shut itself down.
                    return false;
                }
            } elseif ($this->triggerHooks(self::HOOK_INPUT, $client, $input) === false) {
                // This only happens when a hook tells the server to shut itself down.
                return false;
            }
            unset($input);
        }

        // Unset the variables we were holding on to
        unset($read, $write, $except);

        // Tells self::run to Continue the Loop
        return true;
    }

    /**
     * Overrideable Read Functionality.
     *
     * @param Socket $client
     * @throws SocketException
     */
    protected function read(Socket $client): string
    {
        return $client->read($this->maxRead, $this->readType);
    }

    /**
     * Disconnect the supplied Client Socket.
     *
     * @param Socket $client
     * @param string $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return bool Whether or not to continue running the server (true: continue, false: shutdown)
     */
    public function disconnect(Socket $client, string $message = ''): bool
    {
        $clientIndex = array_search($client, $this->clients);
        $return = $this->triggerHooks(
            self::HOOK_DISCONNECT,
            $this->clients[$clientIndex],
            $message
        );

        $this->clients[$clientIndex]->close();
        unset($this->clients[$clientIndex], $client);

        if ($return === false) {
            return false;
        }

        unset($return);

        return true;
    }

    /**
     * Triggers the hooks for the supplied command.
     *
     * @param string $command Hook to listen for (e.g. HOOK_CONNECT, HOOK_INPUT, HOOK_DISCONNECT, HOOK_TIMEOUT)
     * @param Socket $client
     * @param string $input Message Sent along with the Trigger
     *
     * @return bool Whether or not to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerHooks(string $command, Socket $client, string $input = null): bool
    {
        if (isset($this->hooks[$command])) {
            foreach ($this->hooks[$command] as $callable) {
                $continue = $callable($this, $client, $input);

                if ($continue === self::RETURN_HALT_HOOK) {
                    break;
                }
                if ($continue === self::RETURN_HALT_SERVER) {
                    return false;
                }
                unset($continue);
            }
        }

        return true;
    }

    /**
     * Attach a Listener to a Hook.
     *
     * @param string $command Hook to listen for
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     * if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return void
     */
    public function addHook(string $command, callable $callable): void
    {
        if (!isset($this->hooks[$command])) {
            $this->hooks[$command] = [];
        } else {
            $k = array_search($callable, $this->hooks[$command]);
            if ($k !== false) {
                return;
            }
            unset($k);
        }

        $this->hooks[$command][] = $callable;
    }

    /**
     * Remove the provided Callable from the provided Hook.
     *
     * @param string $command Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return void
     */
    public function removeHook(string $command, callable $callable): void
    {
        if (isset($this->hooks[$command]) && in_array($callable, $this->hooks[$command])) {
            $hook = array_search($callable, $this->hooks[$command]);
            unset($this->hooks[$command][$hook], $hook);
        }
    }

    /**
     * Disconnect all the Clients and shut down the server.
     *
     * @return void
     */
    private function shutDownEverything(): void
    {
        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }
        $this->masterSocket->close();
        unset(
            $this->hooks,
            $this->address,
            $this->port,
            $this->timeout,
            $this->domain,
            $this->masterSocket,
            $this->maxClients,
            $this->maxRead,
            $this->clients,
            $this->readType
        );
    }
}
