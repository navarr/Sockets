<?php

namespace Navarr\Socket\Exception;

use Exception;
use Socket;

class SocketException extends Exception
{
    /**
     * @param string|Socket|null $message Provide a resource instead of a message to use the socket_last_error as the
     *                                    message
     */
    public function __construct(string|Socket|null $message = null)
    {
        if ($message instanceof Socket) {
            $errno = socket_last_error($message);
        } elseif (!$message) {
            $errno = socket_last_error();
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
