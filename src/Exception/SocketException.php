<?php

namespace Navarr\Socket\Exception;

class SocketException extends \Exception
{
    protected $message = 'Socket Exception';

    public function __construct($message = null)
    {
        if (!$message) {
            $errno = socket_last_error();
        } elseif (is_resource($message)) {
            $errno = socket_last_error($message);
        } else {
            parent::__construct((string) $message);

            return;
        }

        $error = socket_strerror($errno);

        parent::__construct($error, $errno);

        if (!$message) {
            socket_clear_error();
        } else {
            socket_clear_error($message);
        }
    }
}
