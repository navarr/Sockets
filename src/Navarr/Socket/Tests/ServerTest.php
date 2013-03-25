<?php
namespace Navarr\Socket\Test;

use Navarr\Socket\Socket;
use Navarr\Socket\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testAddingSingleHookWorksProperly()
    {
        $server = new Server('127.0.0.1', 9000);
        $exampleReturn = '__NAVARR_SOCKET_TEST_EXAMPLE_RETURN__';
        $server->addHook(
            Server::HOOK_CONNECT,
            function () {
                return '__NAVARR_SOCKET_TEST_EXAMPLE_RETURN__';
            }
        );

        $serverClass = new \ReflectionClass($server);
        $hooksProperty = $serverClass->getProperty('hooks');
        $hooksProperty->setAccessible(true);

        $hooks = $hooksProperty->getValue($server);

        // Hooks should be a protected property
        $this->assertTrue($hooksProperty->isProtected());
        // Hooks should be an array
        $this->assertTrue(is_array($hooks));
        // Hooks should have a single callable in $hooks[Server::HOOK_CONNECT]
        $this->assertEquals(1, count($hooks[Server::HOOK_CONNECT]));
        // Values in $hooks[Server::HOOK_CONNECT] should be callables
        $this->assertTrue(is_callable($hooks[Server::HOOK_CONNECT][0]));
        // Make sure the callable in $hooks[Server::HOOK_CONNECT] is the return value we expect
        $this->assertEquals($exampleReturn, call_user_func($hooks[Server::HOOK_CONNECT][0]));
    }

    public function testAddingMultipleHooksWorksProperly()
    {
        $server = new Server('127.0.0.1', 9000);
        $server->addHook(
            Server::HOOK_CONNECT,
            function () {
                return '__NAVARR_SOCKET_TEST_EXAMPLE_RETURN_1__';
            }
        );
        $server->addHook(
            Server::HOOK_CONNECT,
            function () {
                return '__NAVARR_SOCKET_TEST_EXAMPLE_RETURN_2__';
            }
        );

        $serverClass = new \ReflectionClass($server);
        $hooksProperty = $serverClass->getProperty('hooks');
        $hooksProperty->setAccessible(true);

        $hooks = $hooksProperty->getValue($server);

        // Hooks should have two callables in $hooks[Server::HOOK_CONNECT]
        $this->assertEquals(2, count($hooks[Server::HOOK_CONNECT]));
        // Values in $hooks[Server::HOOK_CONNECT] should be callables
        foreach ($hooks[Server::HOOK_CONNECT] as $callable) {
            $this->assertTrue(is_callable($callable));
        }
        // Make sure the callable in $hooks[Server::HOOK_CONNECT] is the return value we expect
        $this->assertEquals('__NAVARR_SOCKET_TEST_EXAMPLE_RETURN_2__', call_user_func($hooks[Server::HOOK_CONNECT][1]));
    }
}
