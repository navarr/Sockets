<?php

namespace Navarr;

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

    public static function create($domain, $type, $protocol)
    {
        $return = socket_create($domain, $type, $protocol);
        if ($return === false)
        {
            throw new Socket\Exception(socket_strerror(socket_last_error()),socket_last_error());
        } else {
            return new self($return);
        }
    }
}