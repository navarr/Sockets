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

    /**
     * Verifies that {@see Socket::constructFromResources} works as expected.
     */
    public function testSocketCanBeConstructedFromResources()
    {
        $resourceA = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $resourceB = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        $reflectionMethod = new \ReflectionMethod(Socket::class, 'constructFromResources');
        $reflectionMethod->setAccessible(true);
        $func = $reflectionMethod->getClosure();

        /** @var Socket[] $result */
        $result = $func([$resourceA, $resourceB]);

        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        foreach ($result as $singleResult) {
            $this->assertInstanceOf(Socket::class, $singleResult);
        }

        $resultA = $result[0];
        $resultB = $result[1];

        $reflectionProperty = new \ReflectionProperty($resultA, 'resource');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals($resourceA, $reflectionProperty->getValue($resultA));
        $this->assertEquals($resourceB, $reflectionProperty->getValue($resultB));
    }
}
