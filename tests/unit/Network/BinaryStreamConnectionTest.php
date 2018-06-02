<?php

namespace Tests\unit\Network;


use ModbusTcpClient\Network\BinaryStreamConnection;
use PHPUnit\Framework\TestCase;

class BinaryStreamConnectionTest extends TestCase
{
    public function testSetOptions()
    {
        $connection = BinaryStreamConnection::getBuilder()->setFromOptions([
            'host' => 'localhost',
            'port' => 5022
        ])->build();

        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(5022, $connection->getPort());
    }

    public function testSet()
    {
        $connection = BinaryStreamConnection::getBuilder()
            ->setClient('127.0.0.2')
            ->setClientPort(32000)
            ->setTimeoutSec(1.2)
            ->setConnectTimeoutSec(1.3)
            ->setReadTimeoutSec(1.4)
            ->setWriteTimeoutSec(1.5)
            ->setProtocol('udp')
            ->setHost('localhost')
            ->setPort(5022)
            ->setUri('tcp://192.168.100.1:502')
            ->build();

        $this->assertEquals('127.0.0.2', $connection->getClient());
        $this->assertEquals(32000, $connection->getClientPort());
        $this->assertEquals(1.2, $connection->getTimeoutSec());
        $this->assertEquals(1.3, $connection->getConnectTimeoutSec());
        $this->assertEquals(1.4, $connection->getReadTimeoutSec());
        $this->assertEquals(1.5, $connection->getWriteTimeoutSec());
        $this->assertEquals('udp', $connection->getProtocol());
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(5022, $connection->getPort());
        $this->assertEquals('tcp://192.168.100.1:502', $connection->getUri());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage host or uri property can not be left null or empty!
     */
    public function testCanNotBuildWithoutHostOrUri()
    {
        BinaryStreamConnection::getBuilder()->setPort(5022)->build();
    }

}