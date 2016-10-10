# Sockets 

[![Build Status](https://travis-ci.org/navarr/Sockets.svg)](https://travis-ci.org/navarr/Sockets)
[![Scrutinizer](https://scrutinizer-ci.com/g/navarr/Sockets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/navarr/Sockets/)
[![Code Coverage](https://scrutinizer-ci.com/g/navarr/Sockets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/navarr/Sockets/)
[![Latest Stable Version](https://poser.pugx.org/navarr/sockets/v/stable.svg)](https://packagist.org/packages/navarr/sockets)
[![Total Downloads](https://poser.pugx.org/navarr/sockets/downloads.svg)](https://packagist.org/packages/navarr/sockets) 
[![Latest Unstable Version](https://poser.pugx.org/navarr/sockets/v/unstable.svg)](https://packagist.org/packages/navarr/sockets) 
[![License](https://poser.pugx.org/navarr/sockets/license.svg)](https://packagist.org/packages/navarr/sockets)

Sockets is a PHP Library intent on making working with PHP Sockets easier, including the creation and management of a Socket Server.

## Work in Progress

The code is currently still a work in progress, with the Socket class itself not yet fully complete.  There is a lot I still need to understand about how sockets work both in PHP and probably in C in order to make everything work amazingly.

Not everything is tested yet, and not everything works properly yet.

It is advised not to seriously use this until I create git tag 1.0.0.  There will be breaking changes before then.

## Usage of SocketServer

Using SocketServer is supposed to be an easy and trivial task (and the class should be documented enough to understand what it's doing without me).

### Example of an ECHO Server

```php
<?php

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

class EchoServer extends Server
{
    public function __construct($ip = null, $port = 7)
    {
        parent::__construct($ip, 7);
        $this->addHook(Server::HOOK_CONNECT, array($this, 'onConnect'));
        $this->addHook(Server::HOOK_INPUT, array($this, 'onInput'));
        $this->addHook(Server::HOOK_DISCONNECT, array($this, 'onDisconnect'));
        $this->run();
    }

    public function onConnect(Server $server, Socket $client, $message)
    {
        echo 'Connection Established',"\n";
    }

    public function onInput(Server $server, Socket $client, $message)
    {
        echo 'Received "',$message,'"',"\n";
        $client->write($message, strlen($message));
    }

    public function onDisconnect(Server $server, Socket $client, $message)
    {
        echo 'Disconnection',"\n";
    }
}

$server = new EchoServer('0.0.0.0');

```

## Development

### Documentation

This project uses [phpdoc](https://www.phpdoc.org/) to generate documentation. To generate the documentation, you will need to satisfy some dependencies. First, you need to get [graphviz](http://www.graphviz.org/). It is available through most linux distros, but you can always download it and install it from the site if you aren't on linux. If you install manually, make sure the binaries are on your PATH somewhere. Next run the following commands within this directory (assumes you already have [composer](https://getcomposer.org/) installed and available on your path as `composer`).

```bash
composer install # this will install all of the development dependencies for this project
vendor/phpdocumentor/phpdocumentor/bin/phpdoc -d ./src -t ./docs # this will generate the documentation into a docs directory
```