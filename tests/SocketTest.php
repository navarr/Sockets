<?php

namespace Navarr\Socket\Test;

use Navarr\Socket\Socket;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Verifies that {@see Socket::create}} works as expected. (happy-path).
     */
    public function testSocketCreate()
    {
        // Create the Socket
        $domain = AF_INET;
        $type = SOCK_STREAM;
        $protocol = SOL_TCP;

        $socket = Socket::create($domain, $type, $protocol);

        // Create function should obviously return an instance of the class
        $this->assertTrue($socket instanceof Socket);

        // Lets make sure that the resource is created properly
        $reflectionProperty = new \ReflectionProperty($socket, 'resource');
        $reflectionProperty->setAccessible(true);
        $this->assertEquals('resource', gettype($reflectionProperty->getValue($socket)));

        $propertyDomain = new \ReflectionProperty($socket, 'domain');
        $propertyDomain->setAccessible(true);
        $this->assertEquals($domain, $propertyDomain->getValue($socket));

        $propertyType = new \ReflectionProperty($socket, 'type');
        $propertyType->setAccessible(true);
        $this->assertEquals($type, $propertyType->getValue($socket));

        $propertyProtocol = new \ReflectionProperty($socket, 'protocol');
        $propertyProtocol->setAccessible(true);
        $this->assertEquals($protocol, $propertyProtocol->getValue($socket));
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

    /**
     * Verifies that {@see Socket::mapClassToRawSocket} works as expected.
     */
    public function testMapClassToRawSocket()
    {
        $resourceA = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        $reflectionMethod = new \ReflectionMethod(Socket::class, 'constructFromResources');
        $reflectionMethod->setAccessible(true);
        $func = $reflectionMethod->getClosure();

        $result = $func([$resourceA]);

        $staticReflectionMethod = new \ReflectionMethod(Socket::class, 'mapClassToRawSocket');
        $staticReflectionMethod->setAccessible(true);
        $func = $staticReflectionMethod->getClosure();

        $staticResult = $func($result);

        $this->assertTrue(is_array($staticResult));
        $this->assertEquals($resourceA, $staticResult[0]);
    }
}
