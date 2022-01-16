# Sockets 

[![Latest Stable Version](http://poser.pugx.org/navarr/Sockets/v)](https://packagist.org/packages/navarr/Sockets)
[![Total Downloads](http://poser.pugx.org/navarr/Sockets/downloads)](https://packagist.org/packages/navarr/Sockets)
[![Latest Unstable Version](http://poser.pugx.org/navarr/Sockets/v/unstable)](https://packagist.org/packages/navarr/Sockets)
[![License](http://poser.pugx.org/navarr/Sockets/license)](https://packagist.org/packages/navarr/Sockets)  
![Tests](https://github.com/navarr/Sockets/actions/workflows/commit.yml/badge.svg)
![Code Coverage](https://codecov.io/gh/navarr/Sockets/branch/main/graph/badge.svg?token=C9DtrzMCrD)
[![Mutation Score](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fnavarr%2FSockets%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/navarr/Sockets/main)

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
    const DEFAULT_PORT = 7;

    public function __construct($ip = null, $port = self::DEFAULT_PORT)
    {
        parent::__construct($ip, $port);
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

This project uses [phpdoc](https://www.phpdoc.org/) to generate documentation. To generate the documentation, you will need to satisfy some dependencies. First, you need to get [graphviz](http://www.graphviz.org/). It is available through most Linux distros, but you can always download it and install it from the site if you aren't on Linux. If you install manually, make sure the binaries are on your PATH somewhere. Next, run the following commands within this directory (assumes you already have [composer](https://getcomposer.org/) installed and available on your path as `composer`).

```bash
composer install # this will install all of the development dependencies for this project
vendor/bin/phpdoc -d ./src -t ./docs # this will generate the documentation into a docs directory
```
