<?php

namespace Navarr\Socket;

use Navarr\Socket\Exception\SocketException;

/**
 * Class Socket.
 *
 * <p>A simple wrapper for PHP's socket functions.</p>
 */
class Socket
{
    /**
     * @var resource Will store a reference to the php socket object.
     */
    protected $resource = null;
    /**
     * @var integer Should be set to one of the php predefined constants for Sockets - AF_UNIX, AF_INET, or AF_INET6
     */
    protected $domain = null;
    /**
     * @var integer Should be set to one of the php predefined constants for Sockets - SOCK_STREAM, SOCK_DGRAM, 
     * SOCK_SEQPACKET, SOCK_RAW, SOCK_RDM
     */
    protected $type = null;
    /**
     * @var integer Should be set to the protocol number to be used. Can use getprotobyname to get the value.
     * Alternatively, there are two predefined constants for Sockets that could be used - SOL_TCP, SOL_UDP
     */
    protected $protocol = null;
    /**
     * @var array An internal storage of php socket resources and their associated Socket object.
     */
    protected static $map = [];

    /**
     * Sets up the Socket Resource and stores it in the local map.
     *
     * <p>This class uses the <a href="https://en.wikipedia.org/wiki/Factory_(object-oriented_programming)">
     * Factory pattern</a> to create instances. Please use the <code>create</code> method to create new instances
     * of this class.
     *
     * @see Socket::create()
     *
     * @param resource $resource The php socket resource. This is just a reference to the socket object created using
     * the <code>socket_create</code> method.
     */
    protected function __construct($resource)
    {
        $this->resource = $resource;
        self::$map[(string) $resource] = $this;
    }

    /**
     * Cleans up the Socket and dereferences the internal resource.
     */
    public function __destruct()
    {
        $this->close();
        $this->resource = null;
    }

    /**
     * Return the php socket resource name. 
     *
     * <p>Resources are always converted to strings with the structure "Resource id#1", where 1 is the resource number
     * assigned to the resource by PHP at runtime. While the exact structure of this string should not be relied on and
     * is subject to change, it will always be unique for a given resource within the lifetime of the script execution
     * and won't be reused.</p>
     *
     * <p>If the resource object has been dereferrenced (set to <code>null</code>), this will return an empty
     * string.</p>
     *
     * @return string The string representation of the resource or an empty string if the resource was null.
     */
    public function __toString()
    {
        return (string) $this->resource;
    }

    /**
     * Accept a connection.
     *
     * <p>After the socket socket has been created using <code>create()</code>, bound to a name with
     * <code>bind()</code>, and told to listen for connections with <code>listen()</code>, this function will accept
     * incoming connections on that socket. Once a successful connection is made, a new Socket resource is returned,
     * which may be used for communication. If there are multiple connections queued on the socket, the first will be
     * used. If there are no pending connections, this will block until a connection becomes present. If socket has
     * been made non-blocking using <code>setBlocking()</code>, a <code>SocketException</code> will be thrown.</p>
     *
     * <p>The Socket returned by this method may not be used to accept new connections. The original listening Socket,
     * however, remains open and may be reused.</p>
     *
     * @throws Exception\SocketException If the Socket is set as non-blocking and there are no pending connections.
     *
     * @see Socket::create()
     * @see Socket::bind()
     * @see Socket::listen()
     * @see Socket::setBlocking()
     *
     * @return Socket A new Socket representation of the accepted socket.
     */
    public function accept()
    {
        $return = @socket_accept($this->resource);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return new self($return);
    }

    /**
     * Binds a name to a socket.
     *
     * <p>Binds the name given in address to the php socket resource currently in use. This has to be done before a
     * connection is established using <code>connect()</code> or <code>listen()</code>.</p>
     *
     * @param string $address <p>If the socket is of the AF_INET family, the address is an IP in dotted-quad
     * notation (e.g. <code>127.0.0.1</code>).</p> <p>If the socket is of the AF_UNIX family, the address is the path
     * of the Unix-domain socket (e.g. <code>/tmp/my.sock</code>).</p>
     * @param int    $port <p>(Optional) The port parameter is only used when binding an AF_INET socket, and designates the port
     * on which to listen for connections.</p>
     *
     * @throws Exception\SocketException If the bind was unsuccessful.
     *
     * @return bool <p>Returns <code>true</code> if the bind was successful.</p>
     */
    public function bind($address, $port = 0)
    {
        $return = @socket_bind($this->resource, $address, $port);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return true;
    }

    /**
     * Close the socket.
     *
     * <p>Closes the php socket resource currently in use and removes the reference to it in the internal map.</p>
     *
     * @return void
     */
    public function close()
    {
        unset(self::$map[(string) $this->resource]);
        @socket_close($this->resource);
    }

    /**
     * Connect to a socket.
     *
     * <p>Initiate a connection to the address given using the current php socket resource, which must be a valid
     * socket resource created with <code>create()</code>.
     *
     * @param string $address <p>The address parameter is either an IPv4 address in dotted-quad notation (e.g.
     * <code>127.0.0.1</code>) if the socket is AF_INET, a valid IPv6 address (e.g. <code>::1</code>) if IPv6 support
     * is enabled and the socket is AF_INET6, or the pathname of a Unix domain socket, if the socket family is AF_UNIX.
     * </p>
     * @param int $port <p>(Optional) The port parameter is only used and is mandatory when connecting to an AF_INET or
     * an AF_INET6 socket, and designates the port on the remote host to which a connection should be made.</p>
     *
     * @throws Exception\SocketException If the connect was unsuccessful or if the socket is non-blocking.
     *
     * @see Socket::bind()
     * @see Socket::listen()
     * @see Socket::create()
     *
     * @return bool <p>Returns <code>true</code> if the connect was successful.
     */
    public function connect($address, $port = 0)
    {
        $return = @socket_connect($this->resource, $address, $port);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return true;
    }

    /**
     * @param array $resources
     *
     * @return Socket[]
     */
    protected static function constructFromResources(array $resources)
    {
        $sockets = [];

        foreach ($resources as $resource) {
            $sockets[] = new self($resource);
        }

        return $sockets;
    }

    /**
     * Create a socket.
     *
     * @param int $domain
     * @param int $type
     * @param int $protocol
     *
     * @throws Exception\SocketException
     *
     * @return Socket
     */
    public static function create($domain, $type, $protocol)
    {
        $return = @socket_create($domain, $type, $protocol);

        if ($return === false) {
            throw new SocketException();
        }

        $socket = new self($return);
        $socket->domain = $domain;
        $socket->type = $type;
        $socket->protocol = $protocol;

        return $socket;
    }

    /**
     * @param $port
     * @param int $backlog
     *
     * @throws Exception\SocketException
     *
     * @return Socket
     */
    public static function createListen($port, $backlog = 128)
    {
        $return = @socket_create_listen($port, $backlog);

        if ($return === false) {
            throw new SocketException();
        }

        $socket = new self($return);
        $socket->domain = AF_INET;

        return $socket;
    }

    /**
     * @param $domain
     * @param $type
     * @param $protocol
     *
     * @throws Exception\SocketException
     *
     * @return Socket[]
     */
    public static function createPair($domain, $type, $protocol)
    {
        $array = [];
        $return = @socket_create_pair($domain, $type, $protocol, $array);

        if ($return === false) {
            throw new SocketException();
        }

        $sockets = self::constructFromResources($array);

        foreach ($sockets as $socket) {
            $socket->domain = $domain;
            $socket->type = $type;
            $socket->protocol = $protocol;
        }

        return $sockets;
    }

    /**
     * @param $level
     * @param $optname
     *
     * @throws Exception\SocketException
     *
     * @return mixed
     */
    public function getOption($level, $optname)
    {
        $return = @socket_get_option($this->resource, $level, $optname);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * @param $address
     * @param $port
     *
     * @throws Exception\SocketException
     *
     * @return bool
     */
    public function getPeerName(&$address, &$port)
    {
        $return = @socket_getpeername($this->resource, $address, $port);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * @param string $address
     * @param int    $port
     *
     * @throws Exception\SocketException
     *
     * @return bool
     */
    public function getSockName(&$address, &$port)
    {
        if (!in_array($this->domain, [AF_UNIX, AF_INET, AF_INET6])) {
            return false;
        }

        $return = @socket_getsockname($this->resource, $address, $port);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * @param $stream
     *
     * @throws Exception\SocketException
     *
     * @return Socket
     */
    public static function importStream($stream)
    {
        $return = @socket_import_stream($stream);

        if ($return === false) {
            throw new SocketException($stream);
        }

        return new self($return);
    }

    /**
     * @param int $backlog
     *
     * @throws Exception\SocketException
     *
     * @return bool
     */
    public function listen($backlog = 0)
    {
        $return = socket_listen($this->resource, $backlog);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return true;
    }

    /**
     * @param int $length
     * @param int $type
     *
     * @throws Exception\SocketException
     *
     * @return string
     */
    public function read($length, $type = PHP_BINARY_READ)
    {
        $return = @socket_read($this->resource, $length, $type);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * @param $buffer
     * @param int $length
     * @param int $flags
     *
     * @throws Exception\SocketException
     *
     * @return int
     */
    public function receive(&$buffer, $length, $flags)
    {
        $return = @socket_recv($this->resource, $buffer, $length, $flags);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * @param Socket[] &$read
     * @param Socket[] &$write
     * @param Socket[] &$except
     * @param int      $timeoutSeconds
     * @param int      $timeoutMilliseconds
     * @param Socket[] $read
     *
     * @throws SocketException
     *
     * @return int
     */
    public static function select(
        &$read,
        &$write,
        &$except,
        $timeoutSeconds,
        $timeoutMilliseconds = 0
    ) {
        $readSockets = null;
        $writeSockets = null;
        $exceptSockets = null;

        if (!is_null($read)) {
            $readSockets = self::mapClassToRawSocket($read);
        }
        if (!is_null($write)) {
            $writeSockets = self::mapClassToRawSocket($write);
        }
        if (!is_null($except)) {
            $exceptSockets = self::mapClassToRawSocket($except);
        }

        $return = @socket_select(
            $readSockets,
            $writeSockets,
            $exceptSockets,
            $timeoutSeconds,
            $timeoutMilliseconds
        );

        if ($return === false) {
            throw new SocketException();
        }

        $read = [];
        $write = [];
        $except = [];

        if ($readSockets) {
            $read = static::mapRawSocketToClass($readSockets);
        }
        if ($writeSockets) {
            $write = static::mapRawSocketToClass($writeSockets);
        }
        if ($exceptSockets) {
            $except = static::mapRawSocketToClass($exceptSockets);
        }

        return $return;
    }

    /**
     * Maps an array of {@see Socket}s to an array of socket resources.
     *
     * @param Socket[] $sockets
     *
     * @return resource[]
     */
    protected static function mapClassToRawSocket($sockets)
    {
        return array_map(function (Socket $socket) {
            return $socket->resource;
        }, $sockets);
    }

    /**
     * Maps an array of socket resources to an array of {@see Socket}s.
     *
     * @param resource[] $sockets
     *
     * @return Socket[]
     */
    protected static function mapRawSocketToClass($sockets)
    {
        return array_map(function ($rawSocket) {
            return self::$map[(string) $rawSocket];
        }, $sockets);
    }

    /**
     * @param $buffer
     * @param int $length
     *
     * @throws Exception\SocketException
     *
     * @return int
     */
    public function write($buffer, $length = null)
    {
        if (null === $length) {
            $length = strlen($buffer);
        }

        // make sure everything is written
        do {
            $return = @socket_write($this->resource, $buffer, $length);

            if (false !== $return && $return < $length) {
                $buffer = substr($buffer, $return);
                $length -= $return;
            } else {
                break;
            }
        } while (true);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * Sends data to a connected socket.
     *
     * @param $buffer
     * @param int $flags
     * @param int $length
     *
     * @throws Exception\SocketException
     *
     * @return int
     */
    public function send($buffer, $flags = 0, $length = null)
    {
        if (null === $length) {
            $length = strlen($buffer);
        }

        // make sure everything is written
        do {
            $return = @socket_send($this->resource, $buffer, $length, $flags);

            if (false !== $return && $return < $length) {
                $buffer = substr($buffer, $return);
                $length -= $return;
            } else {
                break;
            }
        } while (true);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * Set the socket to blocking / non blocking.
     *
     * @param bool
     *
     * @return void
     */
    public function setBlocking($bool)
    {
        if ($bool) {
            @socket_set_block($this->resource);
        } else {
            @socket_set_nonblock($this->resource);
        }
    }
}
