<?php

namespace Navarr\Socket\Test;

use Navarr\Socket\Socket;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    public function testSocketCreate()
    {
        // Create the Socket
        $socket = Socket::create(AF_INET, SOCK_STREAM, SOL_TCP);

        // Create function should obviously return an instance of the class
        $this->assertTrue($socket instanceof Socket);

        // Lets make sure that the resource is created properly
        $reflectionProperty = new \ReflectionProperty($socket, 'resource');
        $this->assertTrue($reflectionProperty->isProtected());
        $reflectionProperty->setAccessible(true);
        $this->assertEquals('resource', gettype($reflectionProperty->getValue($socket)));
    }

    /**
     * @expectedException \Navarr\Socket\Exception\SocketException
     */
    public function testSocketCreateWithBadValuesThrowsSocketException()
    {
        Socket::create(9001, 9001, 9001);
    }

    /**
     * @expectedException \Navarr\Socket\Exception\SocketException
     */
    public function testSocketWithResourceThrowsSocketException()
    {
        $socket = Socket::create(AF_INET, SOCK_STREAM, SOL_TCP);
        $socket->write('test', 4);
    }
}
