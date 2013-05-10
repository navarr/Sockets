<?php

namespace Navarr\Socket;

use Navarr\Socket\Exception\SocketException;

/**
 * Class Socket
 *
 * A simple wrapper for PHP's socket functions
 *
 * @package Navarr\Socket
 */
class Socket
{
    protected $resource = null;
    protected $domain = null;
    protected $type = null;
    protected $protocol = null;
    protected static $map = array();

    /**
     * Sets up the Socket Resource
     * @param resource $resource
     */
    protected function __construct($resource)
    {
        $this->resource = $resource;
        self::$map[(string) $resource] = $this;
    }

    /**
     * Cleans up the Socket
     */
    public function __destruct()
    {
        $this->close();
        $this->resource = null;
    }

    /**
     * Return the resource name.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->resource;
    }

    /**
     * Accept a connection
     * 
     * @return Socket
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
     * Bind a socket to an address and port.
     * 
     * @param string $address
     * @param integer $port
     * @return boolean
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
     * @param string $address
     * @param integer $port
     * @return boolean
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
     * @param 
     * @return Socket
     */
    public static function create($domain, $type, $protocol)
    {
        $return = @socket_create($domain, $type, $protocol);

        if ($return === false) {
            throw new SocketException();
        }

        $socket           = new self($return);
        $socket->domain   = $domain;
        $socket->type     = $type;
        $socket->protocol = $protocol;

        return $socket;
    }

    /**
     * 
     * 
     * @param integer $port
     * @param integer $backlog
     * @return Socket
     */
    public static function createListen($port, $backlog = 128)
    {
        $return = @socket_create_listen($port, $backlog);

        if ($return === false) {
            throw new SocketException();
        }

        $socket         = new self($return);
        $socket->domain = AF_INET;

        return $socket;
    }

    /**
     * 
     * 
     * @param 
     * @param 
     * @param 
     * @return Socket
     */
    public static function createPair($domain, $type, $protocol)
    {
        $array  = [];
        $return = @socket_create_pair($domain, $type, $protocol, $array);

        if ($return === false) {
            throw new SocketException();
        }

        $sockets = self::constructFromResources($array);

        foreach ($sockets as $socket) {
            $socket->domain   = $domain;
            $socket->type     = $type;
            $socket->protocol = $protocol;
        }

        return $sockets;
    }

    /**
     * 
     * 
     * @param 
     * @param 
     * @return 
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
     * 
     * 
     * @param 
     * @param 
     * @return 
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
     * 
     * 
     * @param 
     * @param 
     * @return 
     */
    public function getSockName(&$address, &$port)
    {
        if (!in_array($this->domain, array(AF_UNIX, AF_INET, AF_INET6))) {
            return false;
        }

        $return = @socket_getsockname($this->resource, $address, $port);
        
        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return $return;
    }

    /**
     * 
     * 
     * @param 
     * @return Socket
     */
    public static function importStream($stream)
    {
        $return = @socket_import_stream($stream);

        if ($return === false) {
            throw new SocketException($this->resource);
        }

        return new self($return);
    }

    /**
     * 
     * 
     * @param 
     * @return boolean
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
     * 
     * 
     * @param 
     * @param 
     * @return 
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
     * 
     * 
     * @param 
     * @param 
     * @param 
     * @return 
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
     * @param integer $timeoutSeconds
     * @param integer $timeoutMilliseconds
     * @return integer
     * @throws SocketException
     */
    public static function select(
        &$read,
        &$write,
        &$except,
        $timeoutSeconds,
        $timeoutMilliseconds = 0
    ) {
        $readSockets   = null;
        $writeSockets  = null;
        $exceptSockets = null;

        if ($read !== null) {
            $readSockets = [];
            foreach ($read as $socket) {
                $readSockets[] = $socket->resource;
            }
        }
        if ($write !== null) {
            $writeSockets = [];
            foreach ($write as $socket) {
                $writeSockets[] = $socket->resource;
            }
        }
        if ($except !== null) {
            $exceptSockets = [];
            foreach ($except as $socket) {
                $exceptSockets[] = $socket->resource;
            }
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

        $read   = [];
        $write  = [];
        $except = [];

        if ($readSockets) {
            foreach ($readSockets as $rawSocket) {
                $read[] = self::$map[(string) $rawSocket];
            }
        }
        if ($writeSockets) {
            foreach ($writeSockets as $rawSocket) {
                $write[] = self::$map[(string) $rawSocket];
            }
        }
        if ($exceptSockets) {
            foreach ($exceptSockets as $rawSocket) {
                $except[] = self::$map[(string) $rawSocket];
            }
        }

        return $return;
    }

    /**
     * 
     * 
     * @param 
     * @param 
     * @return 
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
                $buffer  = substr($buffer, $return);
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
     * @param boolean
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
