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
     * @param $resource
     */
    private function __construct($resource)
    {
        $this->resource = $resource;
        self::$map[(string)$resource] = $this;
    }

    /**
     * Cleans up the Socket
     */
    public function __destruct()
    {
        @socket_close($this->resource);
        $this->resource = null;
    }

    public function accept()
    {
        $return = @socket_accept($this->resource);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return new self($return);
    }

    public function bind($address, $port = 0)
    {
        $return = @socket_bind($this->resource, $address, $port);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return true;
    }

    public function close()
    {
        unset(self::$map[(string)$this->resource]);
        @socket_close($this->resource);
    }

    public function connect($address, $port = 0)
    {
        $return = @socket_connect($this->resource, $address, $port);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return true;
    }

    private static function constructFromResources($resources)
    {
        $sockets = array();
        foreach ($resources as $resource) {
            $sockets[] = new Socket($resource);
        }
        return $sockets;
    }

    public static function create($domain, $type, $protocol)
    {
        $return = @socket_create($domain, $type, $protocol);
        if ($return === false) {
            SocketException::throwByResource();
        }
        $socket = new self($return);
        $socket->domain = $domain;
        $socket->type = $type;
        $socket->protocol = $protocol;
        return $socket;
    }

    public static function createListen($port, $backlog = 128)
    {
        $return = @socket_create_listen($port, $backlog);
        if ($return === false) {
            SocketException::throwByResource();
        }
        $socket = new self($return);
        $socket->domain = AF_INET;
        return $socket;
    }

    public static function createPair($domain, $type, $protocol)
    {
        $array = array();
        $return = @socket_create_pair($domain, $type, $protocol, $array);
        if ($return === false) {
            SocketException::throwByResource();
        }
        $sockets = self::constructFromResources($array);
        foreach ($sockets as $socket) {
            $socket->domain = $domain;
            $socket->type = $type;
            $socket->protocol = $protocol;
        }
        return $sockets;
    }

    public function getOption($level, $optname)
    {
        $return = socket_get_option($this->resource, $level, $optname);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public function getPeerName(&$address, &$port)
    {
        $return = @socket_getpeername($this->resource, $address, $port);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public function getSockName(&$address, &$port)
    {
        if (!in_array($this->domain, array(AF_UNIX, AF_INET, AF_INET6))) {
            return false;
        }
        $return = socket_getsockname($this->resource, $address, $port);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public static function importStream($stream)
    {
        return new self(@socket_import_stream($stream));
    }

    public function listen($backlog = 0)
    {
        $return = socket_listen($this->resource, $backlog);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return true;
    }

    public function read($length, $type = PHP_BINARY_READ)
    {
        $return = @socket_read($this->resource, $length, $type);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public function receive(&$buffer, $length, $flags)
    {
        $return = @socket_recv($this->resource, $buffer, $length, $flags);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    /**
     * @param Socket[] &$read
     * @param Socket[] &$write
     * @param Socket[] &$except
     * @param int $timeoutSeconds
     * @param int $timeoutMilliseconds
     * @throws SocketException
     * @return int
     */
    public static function select(&$read, &$write, &$except, $timeoutSeconds, $timeoutMilliseconds = 0)
    {
        $readSockets = null;
        $writeSockets = null;
        $exceptSockets = null;
        if ($read !== null) {
            $readSockets = array();
            foreach ($read as $socket) {
                $readSockets[] = $socket->resource;
            }
        }
        if ($write !== null) {
            $writeSockets = array();
            foreach ($write as $socket) {
                $writeSockets[] = $socket->resource;
            }
        }
        if ($except !== null) {
            $exceptSockets = array();
            foreach ($except as $socket) {
                $exceptSockets[] = $socket->resource;
            }
        }

        $return = @socket_select($readSockets, $writeSockets, $exceptSockets, $timeoutSeconds, $timeoutMilliseconds);

        if ($return === false) {
            SocketException::throwByResource();
        }
        $read = array();
        $write = array();
        $except = array();
        if (isset($readSockets)) {
            foreach ($readSockets as $rawSocket) {
                $read[] = self::$map[(string)$rawSocket];
            }
        }
        if (isset($writeSockets)) {
            foreach ($writeSockets as $rawSocket) {
                $write[] = self::$map[(string)$rawSocket];
            }
        }
        if (isset($exceptSockets)) {
            foreach ($exceptSockets as $rawSocket) {
                $except[] = self::$map[(string)$rawSocket];
            }
        }
        return $return;
    }

    public function write($buffer, $length = 0)
    {
        $return = @socket_write($this->resource, $buffer, $length);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public function setBlocking($bool)
    {
        if ($bool) {
            @socket_set_block($this->resource);
        } else {
            @socket_set_nonblock($this->resource);
        }
    }
}
