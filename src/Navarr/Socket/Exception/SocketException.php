<?php

namespace Navarr\Socket\Exception;

class SocketException extends \Exception
{
    protected $message = 'Socket Exception';

    public static function throwByResource($resource = null)
    {
        if ($resource === null) {
            $errno = socket_last_error();
        } else {
            $errno = socket_last_error($resource);
        }
        $error = socket_strerror($errno);

        $exception = new self($error, $errno);

        if ($resource === null) {
            socket_clear_error();
        } else {
            socket_clear_error($resource);
        }

        throw $exception;
    }
}
