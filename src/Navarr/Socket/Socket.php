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

    /**
     * Sets up the Socket Resource
     * @param $resource
     */
    private function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Cleans up the Socket
     */
    public function __destruct()
    {
        socket_close($this->resource);
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
        $return = socket_bind($this->resource, $address, $port);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return true;
    }

    public function close()
    {
        socket_close($this->resource);
    }

    public function connect($address, $port = 0)
    {
        $return = socket_connect($this->resource, $address, $port);
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
        $return = socket_create_pair($domain, $type, $protocol, $array);
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
        $return = socket_getpeername($this->resource, $address, $port);
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
        return new self(socket_import_stream($stream));
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
        $return = socket_read($this->resource, $length, $type);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }

    public function receive(&$buffer, $length, $flags)
    {
        $return = socket_recv($this->resource, $buffer, $length, $flags);
        if ($return === false) {
            SocketException::throwByResource($this->resource);
        }
        return $return;
    }
}
