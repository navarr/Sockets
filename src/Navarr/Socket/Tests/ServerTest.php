<?php
namespace Navarr\Socket\Test;

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testServer()
    {
        $this->markTestIncomplete('Can\'t Yet Test Servers');
        return;

        $server = new Server('127.0.0.1', 9002);
        $server->addHook(
            Server::HOOK_CONNECT,
            function ($server, $client, $input) {
                $this->assertTrue($server instanceof Server);
                $this->assertTrue($client instanceof Socket);
                return 2;
            }
        );
        $server->run();
    }
}
